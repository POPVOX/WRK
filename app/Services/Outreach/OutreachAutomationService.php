<?php

namespace App\Services\Outreach;

use App\Models\Action;
use App\Models\OutreachAutomation;
use App\Models\OutreachCampaign;
use App\Models\OutreachNewsletter;
use RuntimeException;

class OutreachAutomationService
{
    public function __construct(
        protected OutreachAudienceService $audienceService,
        protected OutreachCampaignService $campaignService
    ) {}

    public function runDueAutomations(int $limit = 20): int
    {
        $automations = OutreachAutomation::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit(max(1, min($limit, 200)))
            ->get();

        $processed = 0;
        foreach ($automations as $automation) {
            try {
                $this->execute($automation);
                $processed++;
            } catch (\Throwable $exception) {
                $this->campaignService->log(
                    campaignId: null,
                    userId: $automation->user_id,
                    action: 'automation_failed',
                    summary: 'Automation execution failed.',
                    details: ['error' => $exception->getMessage()],
                    newsletterId: $automation->newsletter_id,
                    automationId: $automation->id
                );
            }
        }

        return $processed;
    }

    public function execute(OutreachAutomation $automation): ?OutreachCampaign
    {
        if ($automation->status !== 'active') {
            return null;
        }

        return match ($automation->action_type) {
            'send_campaign' => $this->executeCampaignFlow($automation, true),
            'draft_campaign' => $this->executeCampaignFlow($automation, false),
            'create_task' => $this->createPlanningTask($automation),
            default => throw new RuntimeException('Unknown outreach automation action type: '.$automation->action_type),
        };
    }

    protected function executeCampaignFlow(OutreachAutomation $automation, bool $queueNow): OutreachCampaign
    {
        $config = is_array($automation->config) ? $automation->config : [];
        $newsletter = $automation->newsletter_id
            ? OutreachNewsletter::query()->find($automation->newsletter_id)
            : null;

        $subject = trim((string) ($config['subject'] ?? ''));
        if ($subject === '') {
            $subject = trim((string) ($newsletter?->default_subject_prefix ?? 'Update')).' '.now()->format('M j, Y');
        }

        $body = trim((string) ($config['body_text'] ?? $automation->prompt ?? ''));
        if ($body === '') {
            $body = "This is an automated outreach draft created by {$automation->name}.";
        }

        $campaign = OutreachCampaign::query()->create([
            'newsletter_id' => $automation->newsletter_id,
            'user_id' => $automation->user_id,
            'project_id' => $automation->project_id,
            'name' => trim((string) ($config['campaign_name'] ?? $automation->name.' '.now()->format('Y-m-d H:i'))),
            'campaign_type' => 'automated',
            'channel' => trim((string) ($config['channel'] ?? 'gmail')) ?: 'gmail',
            'status' => 'draft',
            'subject' => $subject,
            'preheader' => trim((string) ($config['preheader'] ?? '')) ?: null,
            'body_text' => $body,
            'send_mode' => $queueNow ? 'immediate' : 'scheduled',
            'metadata' => [
                'created_by_automation_id' => $automation->id,
                'source' => 'outreach_automation',
            ],
        ]);

        $recipients = $this->resolveRecipientsFromConfig($config, $newsletter?->audience_filters ?? []);
        $this->campaignService->seedRecipients($campaign, $recipients);

        if ($queueNow) {
            $this->campaignService->queueCampaign($campaign);
        }

        $automation->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun($automation),
        ]);

        $this->campaignService->log(
            campaignId: $campaign->id,
            userId: $automation->user_id,
            action: $queueNow ? 'automation_sent_campaign' : 'automation_drafted_campaign',
            summary: $queueNow ? 'Automation created and queued a campaign.' : 'Automation created a draft campaign.',
            details: [
                'automation_id' => $automation->id,
                'recipient_count' => $campaign->recipients_count,
            ],
            newsletterId: $automation->newsletter_id,
            automationId: $automation->id
        );

        return $campaign;
    }

    protected function createPlanningTask(OutreachAutomation $automation): ?OutreachCampaign
    {
        $config = is_array($automation->config) ? $automation->config : [];

        Action::createResilient([
            'title' => trim((string) ($config['task_title'] ?? $automation->name)),
            'description' => trim((string) ($config['task_description'] ?? $automation->prompt ?? '')) ?: null,
            'due_date' => now()->addDay(),
            'priority' => trim((string) ($config['task_priority'] ?? 'medium')) ?: 'medium',
            'status' => Action::STATUS_PENDING,
            'source' => Action::SOURCE_MANUAL,
            'assigned_to' => $automation->user_id,
            'project_id' => $automation->project_id,
        ]);

        $automation->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun($automation),
        ]);

        $this->campaignService->log(
            campaignId: null,
            userId: $automation->user_id,
            action: 'automation_created_task',
            summary: 'Automation created a planning task.',
            details: ['automation_id' => $automation->id],
            newsletterId: $automation->newsletter_id,
            automationId: $automation->id
        );

        return null;
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $newsletterFilters
     * @return array<int,array{email:string,name:?string,person_id:?int}>
     */
    protected function resolveRecipientsFromConfig(array $config, array $newsletterFilters = []): array
    {
        $mode = trim((string) ($config['audience_mode'] ?? 'contacts'));

        if ($mode === 'manual') {
            return $this->audienceService->parseManualRecipients((string) ($config['manual_recipients'] ?? ''));
        }

        $statuses = $config['contact_statuses'] ?? ($newsletterFilters['contact_statuses'] ?? ['active', 'partner', 'prospect']);
        if (! is_array($statuses)) {
            $statuses = ['active', 'partner', 'prospect'];
        }

        return $this->audienceService->fromContactStatuses($statuses);
    }

    protected function calculateNextRun(OutreachAutomation $automation): ?\Illuminate\Support\Carbon
    {
        $config = is_array($automation->config) ? $automation->config : [];
        $intervalHours = (int) ($config['interval_hours'] ?? 24);
        $intervalHours = max(1, min($intervalHours, 24 * 30));

        return now()->addHours($intervalHours);
    }
}
