<?php

namespace App\Livewire\Communications;

use App\Models\OutreachActivityLog;
use App\Models\OutreachAutomation;
use App\Models\OutreachCampaign;
use App\Models\OutreachNewsletter;
use App\Models\OutreachSubstackConnection;
use App\Models\Project;
use App\Services\Outreach\OutreachAudienceService;
use App\Services\Outreach\OutreachAutomationService;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\SlackInsightService;
use App\Services\Outreach\SubstackFeedService;
use App\Services\Outreach\SubstackDraftBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Outreach')]
class OutreachIndex extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'newsletters';

    public bool $migrationReady = false;

    public string $migrationMessage = '';

    public string $runtimeError = '';

    public array $newsletterForm = [
        'name' => '',
        'project_id' => '',
        'channel' => 'hybrid',
        'cadence' => 'weekly',
        'next_issue_date' => '',
        'planning_notes' => '',
        'audience_statuses' => ['active', 'partner', 'prospect'],
    ];

    public array $campaignForm = [
        'name' => '',
        'newsletter_id' => '',
        'project_id' => '',
        'campaign_type' => 'bulk',
        'channel' => 'gmail',
        'subject' => '',
        'preheader' => '',
        'body_text' => '',
        'send_mode' => 'draft',
        'scheduled_for' => '',
        'audience_mode' => 'contacts',
        'contact_statuses' => ['active', 'partner', 'prospect'],
        'manual_recipients' => '',
    ];

    public array $automationForm = [
        'name' => '',
        'newsletter_id' => '',
        'project_id' => '',
        'action_type' => 'draft_campaign',
        'prompt' => '',
        'subject' => '',
        'body_text' => '',
        'audience_mode' => 'contacts',
        'contact_statuses' => ['active', 'partner', 'prospect'],
        'manual_recipients' => '',
        'interval_hours' => 24,
    ];

    public array $substackForm = [
        'publication_name' => '',
        'publication_url' => '',
        'rss_feed_url' => '',
        'email_from' => '',
        'api_key' => '',
    ];

    public array $substackPosts = [];

    public array $substackDraftForm = [
        'newsletter_id' => '',
        'slack_channel_id' => '',
        'days_back' => 7,
        'max_messages' => 80,
    ];

    public array $substackDraftPreview = [];

    public array $substackPresets = [];

    public string $selectedPresetSlug = '';

    public array $presetEditor = [
        'slug' => '',
        'name' => '',
        'lead' => '',
        'publication_url' => '',
        'cadence' => 'weekly',
        'slack_channel_id' => '',
        'default_subject_prefix' => '',
        'project_id' => '',
        'template_sections' => '',
    ];

    public string $presetStatusMessage = '';

    public string $presetStatusType = 'info';

    public array $projectOptions = [];

    public array $newsletterOptions = [];

    public array $contactStatusOptions = [
        'lead' => 'Lead',
        'prospect' => 'Prospect',
        'active' => 'Active',
        'partner' => 'Partner',
        'inactive' => 'Inactive',
    ];

    public function mount(): void
    {
        $this->normalizeTab();
        $this->substackPresets = $this->substackPresetDefinitions();
        $this->substackDraftForm['days_back'] = (int) config('outreach.substack.default_days_back', 7);
        $this->substackDraftForm['max_messages'] = (int) config('outreach.substack.default_message_limit', 80);
        $this->migrationReady = $this->hasOutreachSchema();

        if (! $this->migrationReady) {
            $this->migrationMessage = 'Outreach tables are not available yet. Run migrations to enable newsletters, campaigns, automations, and Substack sync.';
            $this->selectSubstackPreset((string) ($this->substackPresets[0]['slug'] ?? ''));

            return;
        }

        try {
            $this->loadOptions();
            $this->primeSubstackForm();
            $this->primeSubstackDraftDefaults();
            $this->loadSubstackPresetStates();
            $this->selectSubstackPreset((string) ($this->substackPresets[0]['slug'] ?? ''));
        } catch (\Throwable $exception) {
            report($exception);
            $this->runtimeError = 'Outreach loaded with partial data: '.$exception->getMessage();
        }
    }

    public function updatedTab(): void
    {
        $this->normalizeTab();

        if ($this->tab === 'substack' && $this->migrationReady) {
            $this->loadSubstackPresetStates();
            if ($this->selectedPresetSlug === '' && $this->substackPresets !== []) {
                $this->selectSubstackPreset((string) ($this->substackPresets[0]['slug'] ?? ''));
            }
        }
    }

    public function updatedSubstackDraftFormNewsletterId(mixed $value = null): void
    {
        $newsletterId = (int) ($value ?? $this->substackDraftForm['newsletter_id'] ?? 0);
        if ($newsletterId <= 0) {
            return;
        }

        try {
            $newsletter = OutreachNewsletter::query()
                ->where('id', $newsletterId)
                ->where('user_id', Auth::id())
                ->first();
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', type: 'error', message: 'Could not load newsletter defaults for Slack drafting.');

            return;
        }

        if (! $newsletter) {
            return;
        }

        $workflow = $this->extractNewsletterWorkflow($newsletter);
        $existingChannel = trim((string) ($this->substackDraftForm['slack_channel_id'] ?? ''));
        if ($existingChannel === '' && ($workflow['slack_channel_id'] ?? '') !== '') {
            $this->substackDraftForm['slack_channel_id'] = (string) $workflow['slack_channel_id'];
        }
    }

    public function selectSubstackPreset(string $slug): void
    {
        $preset = collect($this->substackPresets)
            ->first(fn ($item) => (string) ($item['slug'] ?? '') === $slug);
        if (! is_array($preset)) {
            return;
        }

        $this->selectedPresetSlug = $slug;
        $this->presetEditor = [
            'slug' => (string) ($preset['slug'] ?? ''),
            'name' => (string) ($preset['name'] ?? ''),
            'lead' => (string) ($preset['lead'] ?? ''),
            'publication_url' => (string) ($preset['publication_url'] ?? ''),
            'cadence' => (string) (($preset['cadence'] ?? '') ?: 'weekly'),
            'slack_channel_id' => (string) ($preset['slack_channel_id'] ?? ''),
            'default_subject_prefix' => (string) ($preset['default_subject_prefix'] ?? ''),
            'project_id' => (string) ($preset['project_id'] ?? ''),
            'template_sections' => implode("\n", (array) ($preset['template_sections'] ?? [])),
        ];

        if (! empty($preset['newsletter_id'])) {
            $this->substackDraftForm['newsletter_id'] = (string) $preset['newsletter_id'];
        }
        if (($preset['slack_channel_id'] ?? '') !== '') {
            $this->substackDraftForm['slack_channel_id'] = (string) $preset['slack_channel_id'];
        }
    }

    public function savePresetConfiguration(OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->setPresetStatus('Run migrations before editing presets.', 'error');

            return;
        }

        if (trim((string) ($this->presetEditor['slug'] ?? '')) === '') {
            $this->setPresetStatus('Select a preset card first.', 'error');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'presetEditor.slug' => ['required', 'string', 'max:120'],
            'presetEditor.name' => ['required', 'string', 'max:160'],
            'presetEditor.lead' => ['nullable', 'string', 'max:160'],
            'presetEditor.publication_url' => ['nullable', 'url', 'max:255'],
            'presetEditor.cadence' => ['nullable', Rule::in(['weekly', 'biweekly', 'monthly', 'ad_hoc'])],
            'presetEditor.slack_channel_id' => ['nullable', 'string', 'max:64'],
            'presetEditor.default_subject_prefix' => ['nullable', 'string', 'max:180'],
            'presetEditor.project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'presetEditor.template_sections' => ['nullable', 'string', 'max:4000'],
        ]);

        $slug = trim((string) $validated['presetEditor']['slug']);
        $definition = collect($this->substackPresetDefinitions())
            ->first(fn ($preset) => is_array($preset) && (string) ($preset['slug'] ?? '') === $slug);
        if (! is_array($definition)) {
            $this->setPresetStatus('Preset definition not found.', 'error');

            return;
        }

        $sections = $this->parseTemplateSections((string) ($validated['presetEditor']['template_sections'] ?? ''));
        if ($sections === []) {
            $sections = array_values(array_filter(array_map(
                static fn ($item): string => trim((string) $item),
                (array) ($definition['template_sections'] ?? [])
            )));
        }

        try {
            $newsletter = $this->findOwnedPresetNewsletter($user->id, $slug);
            if (! $newsletter) {
                $slugCandidate = $slug;
                $slugTakenByOther = OutreachNewsletter::query()
                    ->where('slug', $slug)
                    ->where('user_id', '!=', $user->id)
                    ->exists();
                if ($slugTakenByOther) {
                    $slugCandidate = $slug.'-u'.$user->id;
                }
                $newsletter = new OutreachNewsletter([
                    'user_id' => $user->id,
                    'slug' => $slugCandidate,
                ]);
            }

            $workflow = [
                'lead' => trim((string) ($validated['presetEditor']['lead'] ?? '')),
                'slack_channel_id' => trim((string) ($validated['presetEditor']['slack_channel_id'] ?? '')),
                'template_sections' => $sections,
            ];

            $newsletter->fill([
                'project_id' => ($validated['presetEditor']['project_id'] ?? null)
                    ?: $newsletter->project_id
                    ?: $this->resolvePresetProjectId((array) ($definition['project_match_terms'] ?? [])),
                'name' => trim((string) $validated['presetEditor']['name']),
                'channel' => 'substack',
                'status' => $newsletter->status ?: 'planning',
                'cadence' => trim((string) ($validated['presetEditor']['cadence'] ?? '')) ?: null,
                'substack_publication_url' => trim((string) ($validated['presetEditor']['publication_url'] ?? '')) ?: null,
                'default_subject_prefix' => trim((string) ($validated['presetEditor']['default_subject_prefix'] ?? '')) ?: null,
                'publishing_checklist' => $this->mergeNewsletterWorkflow($newsletter->publishing_checklist, $workflow),
            ]);
            $newsletter->save();

            $campaignService->log(
                campaignId: null,
                userId: $user->id,
                action: 'substack_preset_saved',
                summary: 'Saved Substack preset configuration.',
                details: [
                    'preset_slug' => $slug,
                    'newsletter_id' => $newsletter->id,
                    'slack_channel_id' => $workflow['slack_channel_id'],
                ],
                newsletterId: $newsletter->id
            );
        } catch (\Throwable $exception) {
            report($exception);
            $this->setPresetStatus('Could not save preset: '.$exception->getMessage(), 'error');

            return;
        }

        $this->loadOptions();
        $this->loadSubstackPresetStates();
        $this->selectSubstackPreset($slug);
        $this->primeSubstackDraftDefaults();
        $this->setPresetStatus('Preset saved. You can now generate drafts from this channel.', 'success');
    }

    public function createNewsletter(OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before creating newsletters.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'newsletterForm.name' => ['required', 'string', 'max:160'],
            'newsletterForm.project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'newsletterForm.channel' => ['required', Rule::in(['gmail', 'substack', 'hybrid'])],
            'newsletterForm.cadence' => ['nullable', Rule::in(['weekly', 'biweekly', 'monthly', 'ad_hoc'])],
            'newsletterForm.next_issue_date' => ['nullable', 'date'],
            'newsletterForm.planning_notes' => ['nullable', 'string', 'max:4000'],
            'newsletterForm.audience_statuses' => ['array'],
            'newsletterForm.audience_statuses.*' => ['string', Rule::in(array_keys($this->contactStatusOptions))],
        ]);

        $name = trim((string) $validated['newsletterForm']['name']);
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'newsletter-'.Str::lower(Str::random(8));
        }
        if (OutreachNewsletter::query()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $newsletter = OutreachNewsletter::query()->create([
            'user_id' => $user->id,
            'project_id' => $validated['newsletterForm']['project_id'] ?: null,
            'name' => $name,
            'slug' => $slug,
            'channel' => $validated['newsletterForm']['channel'],
            'status' => 'planning',
            'cadence' => $validated['newsletterForm']['cadence'] ?: null,
            'audience_filters' => [
                'contact_statuses' => array_values(array_unique($validated['newsletterForm']['audience_statuses'] ?? [])),
            ],
            'planning_notes' => trim((string) ($validated['newsletterForm']['planning_notes'] ?? '')) ?: null,
            'next_issue_date' => $validated['newsletterForm']['next_issue_date'] ?: null,
        ]);

        $campaignService->log(
            campaignId: null,
            userId: $user->id,
            action: 'newsletter_created',
            summary: 'Created outreach newsletter plan.',
            details: ['newsletter_id' => $newsletter->id, 'name' => $newsletter->name],
            newsletterId: $newsletter->id
        );

        $this->newsletterForm['name'] = '';
        $this->newsletterForm['planning_notes'] = '';
        $this->newsletterForm['next_issue_date'] = '';
        $this->newsletterForm['project_id'] = '';

        $this->loadOptions();
        $this->dispatch('notify', type: 'success', message: 'Newsletter plan created.');
    }

    public function createCampaign(OutreachAudienceService $audienceService, OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before creating campaigns.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'campaignForm.name' => ['required', 'string', 'max:180'],
            'campaignForm.newsletter_id' => ['nullable', 'integer', Rule::exists('outreach_newsletters', 'id')],
            'campaignForm.project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'campaignForm.campaign_type' => ['required', Rule::in(['newsletter', 'bulk', 'automated'])],
            'campaignForm.channel' => ['required', Rule::in(['gmail', 'substack', 'hybrid'])],
            'campaignForm.subject' => ['required', 'string', 'max:255'],
            'campaignForm.preheader' => ['nullable', 'string', 'max:255'],
            'campaignForm.body_text' => ['required', 'string', 'max:20000'],
            'campaignForm.send_mode' => ['required', Rule::in(['draft', 'immediate', 'scheduled'])],
            'campaignForm.scheduled_for' => ['nullable', 'date', 'after:now'],
            'campaignForm.audience_mode' => ['required', Rule::in(['contacts', 'manual'])],
            'campaignForm.contact_statuses' => ['array'],
            'campaignForm.contact_statuses.*' => ['string', Rule::in(array_keys($this->contactStatusOptions))],
            'campaignForm.manual_recipients' => ['nullable', 'string'],
        ]);

        $status = match ($validated['campaignForm']['send_mode']) {
            'scheduled' => 'scheduled',
            default => 'draft',
        };

        $campaign = OutreachCampaign::query()->create([
            'newsletter_id' => $validated['campaignForm']['newsletter_id'] ?: null,
            'user_id' => $user->id,
            'project_id' => $validated['campaignForm']['project_id'] ?: null,
            'name' => trim((string) $validated['campaignForm']['name']),
            'campaign_type' => $validated['campaignForm']['campaign_type'],
            'channel' => $validated['campaignForm']['channel'],
            'status' => $status,
            'subject' => trim((string) $validated['campaignForm']['subject']),
            'preheader' => trim((string) ($validated['campaignForm']['preheader'] ?? '')) ?: null,
            'body_text' => trim((string) $validated['campaignForm']['body_text']),
            'send_mode' => $validated['campaignForm']['send_mode'],
            'scheduled_for' => $validated['campaignForm']['send_mode'] === 'scheduled'
                ? $validated['campaignForm']['scheduled_for']
                : null,
            'metadata' => [
                'audience_mode' => $validated['campaignForm']['audience_mode'],
                'contact_statuses' => array_values(array_unique($validated['campaignForm']['contact_statuses'] ?? [])),
            ],
        ]);

        $recipients = $validated['campaignForm']['audience_mode'] === 'manual'
            ? $audienceService->parseManualRecipients((string) ($validated['campaignForm']['manual_recipients'] ?? ''))
            : $audienceService->fromContactStatuses($validated['campaignForm']['contact_statuses'] ?? []);

        $recipientCount = $campaignService->seedRecipients($campaign, $recipients);
        if ($recipientCount === 0) {
            $campaign->update(['status' => 'failed']);
            $this->dispatch('notify', type: 'error', message: 'Campaign created but no valid recipients were found.');

            return;
        }

        $campaignService->log(
            campaignId: $campaign->id,
            userId: $user->id,
            action: 'campaign_created',
            summary: 'Created outreach campaign.',
            details: ['recipient_count' => $recipientCount, 'status' => $campaign->status],
            newsletterId: $campaign->newsletter_id
        );

        if ($validated['campaignForm']['send_mode'] === 'immediate') {
            try {
                $queued = $campaignService->queueCampaign($campaign);
                $this->dispatch('notify', type: 'success', message: "Campaign queued ({$queued} recipients).");
            } catch (\Throwable $exception) {
                $this->dispatch('notify', type: 'error', message: $exception->getMessage());
            }
        } elseif ($validated['campaignForm']['send_mode'] === 'scheduled') {
            $this->dispatch('notify', type: 'success', message: "Campaign scheduled for {$validated['campaignForm']['scheduled_for']}.");
        } else {
            $this->dispatch('notify', type: 'success', message: 'Campaign saved as draft.');
        }

        $this->resetCampaignForm();
    }

    public function queueCampaignNow(int $campaignId, OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before queueing campaigns.');

            return;
        }

        $campaign = OutreachCampaign::query()
            ->where('id', $campaignId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $campaign) {
            $this->dispatch('notify', type: 'error', message: 'Campaign not found.');

            return;
        }

        try {
            $queued = $campaignService->queueCampaign($campaign);
            $this->dispatch('notify', type: 'success', message: "Campaign queued ({$queued} recipients).");
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
    }

    public function cancelCampaign(int $campaignId, OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before managing campaigns.');

            return;
        }

        $campaign = OutreachCampaign::query()
            ->where('id', $campaignId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $campaign) {
            return;
        }

        $campaign->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        $campaignService->log(
            campaignId: $campaign->id,
            userId: $campaign->user_id,
            action: 'campaign_cancelled',
            summary: 'Cancelled outreach campaign.',
            details: [],
            newsletterId: $campaign->newsletter_id
        );

        $this->dispatch('notify', type: 'success', message: 'Campaign cancelled.');
    }

    public function createAutomation(OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before creating automations.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'automationForm.name' => ['required', 'string', 'max:180'],
            'automationForm.newsletter_id' => ['nullable', 'integer', Rule::exists('outreach_newsletters', 'id')],
            'automationForm.project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'automationForm.action_type' => ['required', Rule::in(['draft_campaign', 'send_campaign', 'create_task'])],
            'automationForm.prompt' => ['nullable', 'string', 'max:4000'],
            'automationForm.subject' => ['nullable', 'string', 'max:255'],
            'automationForm.body_text' => ['nullable', 'string', 'max:20000'],
            'automationForm.audience_mode' => ['required', Rule::in(['contacts', 'manual'])],
            'automationForm.contact_statuses' => ['array'],
            'automationForm.contact_statuses.*' => ['string', Rule::in(array_keys($this->contactStatusOptions))],
            'automationForm.manual_recipients' => ['nullable', 'string'],
            'automationForm.interval_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        $automation = OutreachAutomation::query()->create([
            'user_id' => $user->id,
            'newsletter_id' => $validated['automationForm']['newsletter_id'] ?: null,
            'project_id' => $validated['automationForm']['project_id'] ?: null,
            'name' => trim((string) $validated['automationForm']['name']),
            'status' => 'active',
            'trigger_type' => 'schedule',
            'rrule' => null,
            'timezone' => (string) ($user->timezone ?: config('app.timezone', 'UTC')),
            'action_type' => $validated['automationForm']['action_type'],
            'prompt' => trim((string) ($validated['automationForm']['prompt'] ?? '')) ?: null,
            'config' => [
                'subject' => trim((string) ($validated['automationForm']['subject'] ?? '')),
                'body_text' => trim((string) ($validated['automationForm']['body_text'] ?? '')),
                'audience_mode' => $validated['automationForm']['audience_mode'],
                'contact_statuses' => array_values(array_unique($validated['automationForm']['contact_statuses'] ?? [])),
                'manual_recipients' => (string) ($validated['automationForm']['manual_recipients'] ?? ''),
                'interval_hours' => (int) $validated['automationForm']['interval_hours'],
            ],
            'next_run_at' => now()->addHours((int) $validated['automationForm']['interval_hours']),
        ]);

        $campaignService->log(
            campaignId: null,
            userId: $user->id,
            action: 'automation_created',
            summary: 'Created outreach automation.',
            details: ['automation_id' => $automation->id, 'action_type' => $automation->action_type],
            newsletterId: $automation->newsletter_id,
            automationId: $automation->id
        );

        $this->resetAutomationForm();
        $this->dispatch('notify', type: 'success', message: 'Automation recipe created.');
    }

    public function runAutomationNow(int $automationId, OutreachAutomationService $automationService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before running automations.');

            return;
        }

        $automation = OutreachAutomation::query()
            ->where('id', $automationId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $automation) {
            return;
        }

        $automationService->execute($automation);
        $this->dispatch('notify', type: 'success', message: 'Automation executed.');
    }

    public function toggleAutomation(int $automationId): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before updating automations.');

            return;
        }

        $automation = OutreachAutomation::query()
            ->where('id', $automationId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $automation) {
            return;
        }

        $automation->update([
            'status' => $automation->status === 'active' ? 'paused' : 'active',
        ]);

        $this->dispatch('notify', type: 'success', message: 'Automation status updated.');
    }

    public function saveSubstackConnection(): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before saving Substack settings.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'substackForm.publication_name' => ['nullable', 'string', 'max:180'],
            'substackForm.publication_url' => ['nullable', 'url', 'max:255'],
            'substackForm.rss_feed_url' => ['nullable', 'url', 'max:255'],
            'substackForm.email_from' => ['nullable', 'email', 'max:255'],
            'substackForm.api_key' => ['nullable', 'string', 'max:2000'],
        ]);

        $connection = OutreachSubstackConnection::query()->firstOrNew(['user_id' => $user->id]);
        $connection->fill([
            'publication_name' => trim((string) ($validated['substackForm']['publication_name'] ?? '')) ?: null,
            'publication_url' => trim((string) ($validated['substackForm']['publication_url'] ?? '')) ?: null,
            'rss_feed_url' => trim((string) ($validated['substackForm']['rss_feed_url'] ?? '')) ?: null,
            'email_from' => trim((string) ($validated['substackForm']['email_from'] ?? '')) ?: null,
            'api_key' => trim((string) ($validated['substackForm']['api_key'] ?? '')) ?: null,
            'status' => $connection->exists ? $connection->status : 'disconnected',
        ]);
        $connection->save();

        $this->dispatch('notify', type: 'success', message: 'Substack settings saved.');
    }

    public function syncSubstack(SubstackFeedService $substackFeedService, OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before syncing Substack.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $connection = OutreachSubstackConnection::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'disconnected']
        );

        try {
            $this->substackPosts = $substackFeedService->fetchRecentPosts($connection, 8);
            $campaignService->log(
                campaignId: null,
                userId: $user->id,
                action: 'substack_sync_success',
                summary: 'Substack feed sync completed.',
                details: ['posts' => count($this->substackPosts)]
            );

            $this->dispatch('notify', type: 'success', message: 'Substack feed synced.');
        } catch (\Throwable $exception) {
            $connection->update([
                'status' => 'error',
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ]);
            $campaignService->log(
                campaignId: null,
                userId: $user->id,
                action: 'substack_sync_failed',
                summary: 'Substack feed sync failed.',
                details: ['error' => $exception->getMessage()]
            );

            $this->dispatch('notify', type: 'error', message: 'Substack sync failed: '.$exception->getMessage());
        }
    }

    public function installSubstackPresets(OutreachCampaignService $campaignService): void
    {
        if (! $this->migrationReady) {
            $this->setPresetStatus('Run migrations before installing Substack presets.', 'error');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $presets = $this->substackPresetDefinitions();
        if ($presets === []) {
            $this->setPresetStatus('No Substack presets are configured.', 'error');

            return;
        }

        $created = 0;
        $updated = 0;

        try {
            foreach ($presets as $preset) {
                $slug = trim((string) ($preset['slug'] ?? ''));
                $name = trim((string) ($preset['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    continue;
                }

                $ownedNewsletter = $this->findOwnedPresetNewsletter((int) $user->id, $slug);

                if ($ownedNewsletter) {
                    $newsletter = $ownedNewsletter;
                } else {
                    $slugCandidate = $slug;
                    $slugTakenByOther = OutreachNewsletter::query()
                        ->where('slug', $slug)
                        ->where('user_id', '!=', $user->id)
                        ->exists();
                    if ($slugTakenByOther) {
                        $slugCandidate = $slug.'-u'.$user->id;
                    }

                    $newsletter = new OutreachNewsletter([
                        'user_id' => $user->id,
                        'slug' => $slugCandidate,
                    ]);
                }

                $isNew = ! $newsletter->exists;
                $existingWorkflow = $this->extractNewsletterWorkflow($newsletter);
                $nextWorkflow = [
                    'lead' => trim((string) ($existingWorkflow['lead'] ?? '')) !== ''
                        ? (string) $existingWorkflow['lead']
                        : trim((string) ($preset['lead'] ?? '')),
                    'slack_channel_id' => trim((string) ($existingWorkflow['slack_channel_id'] ?? '')) !== ''
                        ? (string) $existingWorkflow['slack_channel_id']
                        : trim((string) ($preset['slack_channel_id'] ?? '')),
                    'template_sections' => $existingWorkflow['template_sections'] !== []
                        ? $existingWorkflow['template_sections']
                        : array_values(array_filter(array_map(
                            static fn ($item): string => trim((string) $item),
                            (array) ($preset['template_sections'] ?? [])
                        ))),
                ];

                $newsletter->fill([
                    'project_id' => $newsletter->project_id ?: $this->resolvePresetProjectId((array) ($preset['project_match_terms'] ?? [])),
                    'name' => $newsletter->name ?: $name,
                    'channel' => $newsletter->channel ?: 'substack',
                    'status' => $newsletter->status ?: 'planning',
                    'cadence' => $newsletter->cadence ?: ((string) ($preset['cadence'] ?? '') ?: null),
                    'substack_publication_url' => $newsletter->substack_publication_url ?: (trim((string) ($preset['publication_url'] ?? '')) ?: null),
                    'default_subject_prefix' => $newsletter->default_subject_prefix ?: (trim((string) ($preset['default_subject_prefix'] ?? '')) ?: null),
                    'publishing_checklist' => $this->mergeNewsletterWorkflow($newsletter->publishing_checklist, $nextWorkflow),
                ]);
                $newsletter->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->setPresetStatus('Could not install presets: '.$exception->getMessage(), 'error');

            return;
        }

        $this->loadOptions();
        $this->loadSubstackPresetStates();
        $this->primeSubstackDraftDefaults();

        $campaignService->log(
            campaignId: null,
            userId: $user->id,
            action: 'substack_presets_installed',
            summary: 'Installed default Substack newsletter presets.',
            details: ['created' => $created, 'updated' => $updated]
        );

        $this->setPresetStatus("Presets applied. Created {$created}, updated {$updated}.", 'success');
    }

    public function generateSubstackDraftFromSlack(
        SlackInsightService $slackInsightService,
        SubstackDraftBuilder $substackDraftBuilder,
        OutreachCampaignService $campaignService
    ): void {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before generating Substack drafts.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'substackDraftForm.newsletter_id' => ['required', 'integer', Rule::exists('outreach_newsletters', 'id')],
            'substackDraftForm.slack_channel_id' => ['nullable', 'string', 'max:64'],
            'substackDraftForm.days_back' => ['required', 'integer', 'min:1', 'max:30'],
            'substackDraftForm.max_messages' => ['required', 'integer', 'min:10', 'max:400'],
        ]);

        $newsletterId = (int) ($validated['substackDraftForm']['newsletter_id'] ?? 0);
        $newsletter = OutreachNewsletter::query()
            ->where('id', $newsletterId)
            ->where('user_id', $user->id)
            ->first();

        if (! $newsletter) {
            $this->dispatch('notify', type: 'error', message: 'Newsletter not found.');

            return;
        }

        $workflow = $this->extractNewsletterWorkflow($newsletter);
        $channelId = trim((string) ($validated['substackDraftForm']['slack_channel_id'] ?? ''));
        if ($channelId === '') {
            $channelId = trim((string) ($workflow['slack_channel_id'] ?? ''));
            $this->substackDraftForm['slack_channel_id'] = $channelId;
        }

        if ($channelId === '') {
            $this->dispatch('notify', type: 'error', message: 'Set a Slack channel ID first (or install presets with channel IDs).');

            return;
        }

        try {
            $digest = $slackInsightService->fetchChannelInsights(
                channelId: $channelId,
                daysBack: (int) $validated['substackDraftForm']['days_back'],
                maxMessages: (int) $validated['substackDraftForm']['max_messages'],
            );

            $draft = $substackDraftBuilder->build(
                newsletter: $newsletter,
                messages: (array) ($digest['messages'] ?? []),
                context: [
                    'lead' => $workflow['lead'] ?? null,
                    'publication_url' => $newsletter->substack_publication_url,
                    'template_sections' => $workflow['template_sections'] ?? [],
                ]
            );

            $campaign = OutreachCampaign::query()->create([
                'newsletter_id' => $newsletter->id,
                'user_id' => $user->id,
                'project_id' => $newsletter->project_id,
                'name' => $draft['campaign_name'],
                'campaign_type' => 'newsletter',
                'channel' => 'substack',
                'status' => 'draft',
                'subject' => $draft['subject'],
                'preheader' => null,
                'body_text' => $draft['body_markdown'],
                'body_markdown' => $draft['body_markdown'],
                'send_mode' => 'draft',
                'metadata' => [
                    'source' => 'slack_substack_draft',
                    'slack_channel_id' => $channelId,
                    'days_back' => (int) $validated['substackDraftForm']['days_back'],
                    'messages_scanned' => (int) ($digest['message_count'] ?? 0),
                    'key_signal_count' => count((array) ($draft['key_signals'] ?? [])),
                    'link_roundup_count' => count((array) ($draft['link_roundup'] ?? [])),
                ],
            ]);

            $this->substackDraftPreview = [
                'campaign_id' => (int) $campaign->id,
                'campaign_name' => (string) ($draft['campaign_name'] ?? ''),
                'subject' => (string) ($draft['subject'] ?? ''),
                'body_markdown' => (string) ($draft['body_markdown'] ?? ''),
                'key_signals' => (array) ($draft['key_signals'] ?? []),
                'link_roundup' => (array) ($draft['link_roundup'] ?? []),
                'messages_scanned' => (int) ($digest['message_count'] ?? 0),
                'generated_at' => now()->toDateTimeString(),
            ];

            $campaignService->log(
                campaignId: $campaign->id,
                userId: $user->id,
                action: 'substack_draft_generated_from_slack',
                summary: 'Generated Substack draft from Slack insights.',
                details: [
                    'newsletter_id' => $newsletter->id,
                    'newsletter' => $newsletter->name,
                    'campaign_id' => $campaign->id,
                    'slack_channel_id' => $channelId,
                    'messages_scanned' => (int) ($digest['message_count'] ?? 0),
                    'key_signal_count' => count((array) ($draft['key_signals'] ?? [])),
                    'link_roundup_count' => count((array) ($draft['link_roundup'] ?? [])),
                ],
                newsletterId: $newsletter->id
            );

            $this->dispatch('notify', type: 'success', message: 'Substack draft created from Slack insights.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', type: 'error', message: 'Draft generation failed: '.$exception->getMessage());
        }
    }

    protected function loadOptions(): void
    {
        if (! $this->migrationReady) {
            $this->projectOptions = [];
            $this->newsletterOptions = [];

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        try {
            $this->projectOptions = Project::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'id' => (int) $project->id,
                    'name' => (string) $project->name,
                ])
                ->all();

            $this->newsletterOptions = OutreachNewsletter::query()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (OutreachNewsletter $newsletter): array => [
                    'id' => (int) $newsletter->id,
                    'name' => (string) $newsletter->name,
                ])
                ->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->projectOptions = [];
            $this->newsletterOptions = [];
            $this->runtimeError = 'Outreach options failed to load: '.$exception->getMessage();
        }
    }

    protected function primeSubstackForm(): void
    {
        if (! $this->migrationReady) {
            return;
        }

        try {
            $connection = OutreachSubstackConnection::query()
                ->where('user_id', Auth::id())
                ->first();
        } catch (\Throwable $exception) {
            report($exception);
            $this->runtimeError = 'Substack connection failed to load: '.$exception->getMessage();

            return;
        }

        if (! $connection) {
            return;
        }

        $this->substackForm['publication_name'] = (string) ($connection->publication_name ?? '');
        $this->substackForm['publication_url'] = (string) ($connection->publication_url ?? '');
        $this->substackForm['rss_feed_url'] = (string) ($connection->rss_feed_url ?? '');
        $this->substackForm['email_from'] = (string) ($connection->email_from ?? '');
        $this->substackForm['api_key'] = (string) ($connection->api_key ?? '');
    }

    protected function primeSubstackDraftDefaults(): void
    {
        if (! $this->migrationReady || (int) Auth::id() <= 0) {
            return;
        }

        $selectedNewsletterId = (int) ($this->substackDraftForm['newsletter_id'] ?? 0);
        if ($selectedNewsletterId <= 0) {
            $firstNewsletterId = OutreachNewsletter::query()
                ->where('user_id', (int) Auth::id())
                ->orderBy('name')
                ->value('id');
            if ($firstNewsletterId) {
                $selectedNewsletterId = (int) $firstNewsletterId;
                $this->substackDraftForm['newsletter_id'] = (string) $selectedNewsletterId;
            }
        }

        if ($selectedNewsletterId <= 0) {
            return;
        }

        $newsletter = OutreachNewsletter::query()
            ->where('id', $selectedNewsletterId)
            ->where('user_id', Auth::id())
            ->first();
        if (! $newsletter) {
            return;
        }

        if (trim((string) ($this->substackDraftForm['slack_channel_id'] ?? '')) === '') {
            $workflow = $this->extractNewsletterWorkflow($newsletter);
            if (($workflow['slack_channel_id'] ?? '') !== '') {
                $this->substackDraftForm['slack_channel_id'] = (string) $workflow['slack_channel_id'];
            }
        }
    }

    protected function loadSubstackPresetStates(): void
    {
        $definitions = $this->substackPresetDefinitions();
        $userId = (int) Auth::id();

        if (! $this->migrationReady || $userId <= 0) {
            $this->substackPresets = $definitions;

            return;
        }

        $slugs = collect($definitions)
            ->map(static fn ($preset): string => trim((string) ($preset['slug'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $slugVariants = collect($slugs)
            ->flatMap(static fn (string $slug): array => [$slug, $slug.'-u'.$userId])
            ->values()
            ->all();

        $newsletters = OutreachNewsletter::query()
            ->where('user_id', $userId)
            ->whereIn('slug', $slugVariants)
            ->get();

        $next = [];
        foreach ($definitions as $preset) {
            $slug = trim((string) ($preset['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            /** @var OutreachNewsletter|null $newsletter */
            $newsletter = $newsletters->first(
                static fn (OutreachNewsletter $item): bool => in_array($item->slug, [$slug, $slug.'-u'.$userId], true)
            );

            $workflow = $newsletter ? $this->extractNewsletterWorkflow($newsletter) : [
                'lead' => '',
                'slack_channel_id' => '',
                'template_sections' => [],
            ];

            $defaultSections = array_values(array_filter(array_map(
                static fn ($item): string => trim((string) $item),
                (array) ($preset['template_sections'] ?? [])
            )));

            $next[] = [
                'slug' => $slug,
                'name' => (string) ($newsletter?->name ?? $preset['name'] ?? $slug),
                'lead' => (string) (($workflow['lead'] ?? '') !== '' ? $workflow['lead'] : (string) ($preset['lead'] ?? '')),
                'publication_url' => (string) ($newsletter?->substack_publication_url ?? $preset['publication_url'] ?? ''),
                'cadence' => (string) ($newsletter?->cadence ?? $preset['cadence'] ?? 'weekly'),
                'slack_channel_id' => (string) (($workflow['slack_channel_id'] ?? '') !== '' ? $workflow['slack_channel_id'] : (string) ($preset['slack_channel_id'] ?? '')),
                'default_subject_prefix' => (string) ($newsletter?->default_subject_prefix ?? $preset['default_subject_prefix'] ?? ''),
                'template_sections' => (array) (($workflow['template_sections'] ?? []) !== [] ? $workflow['template_sections'] : $defaultSections),
                'project_id' => $newsletter?->project_id ?? null,
                'newsletter_id' => $newsletter?->id ?? null,
                'installed' => $newsletter !== null,
            ];
        }

        $this->substackPresets = $next;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function substackPresetDefinitions(): array
    {
        return array_values(array_filter(
            (array) config('outreach.substack.presets', []),
            static fn ($preset): bool => is_array($preset) && trim((string) ($preset['slug'] ?? '')) !== ''
        ));
    }

    /**
     * @return array<int,string>
     */
    protected function parseTemplateSections(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $lines
        )));
    }

    protected function findOwnedPresetNewsletter(int $userId, string $slug): ?OutreachNewsletter
    {
        return OutreachNewsletter::query()
            ->where('user_id', $userId)
            ->whereIn('slug', [$slug, $slug.'-u'.$userId])
            ->first();
    }

    protected function setPresetStatus(string $message, string $type = 'info'): void
    {
        $this->presetStatusMessage = trim($message);
        $this->presetStatusType = in_array($type, ['success', 'error', 'info'], true) ? $type : 'info';
        $this->dispatch('notify', type: $this->presetStatusType === 'error' ? 'error' : 'success', message: $this->presetStatusMessage);
    }

    /**
     * @param  array<int,mixed>  $terms
     */
    protected function resolvePresetProjectId(array $terms): ?int
    {
        $cleanTerms = array_values(array_filter(array_map(
            static fn ($term): string => trim((string) $term),
            $terms
        )));

        if ($cleanTerms === []) {
            return null;
        }

        foreach ($cleanTerms as $term) {
            $projectId = Project::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%'.Str::lower($term).'%'])
                ->value('id');
            if ($projectId) {
                return (int) $projectId;
            }
        }

        return null;
    }

    /**
     * @return array{lead:string,slack_channel_id:string,template_sections:array<int,string>}
     */
    protected function extractNewsletterWorkflow(OutreachNewsletter $newsletter): array
    {
        $checklist = (array) ($newsletter->publishing_checklist ?? []);
        $workflow = [];

        if (isset($checklist['workflow']) && is_array($checklist['workflow'])) {
            $workflow = $checklist['workflow'];
        }

        $sections = array_values(array_filter(array_map(
            static fn ($section): string => trim((string) $section),
            (array) ($workflow['template_sections'] ?? [])
        )));

        return [
            'lead' => trim((string) ($workflow['lead'] ?? '')),
            'slack_channel_id' => trim((string) ($workflow['slack_channel_id'] ?? '')),
            'template_sections' => $sections,
        ];
    }

    /**
     * @param  mixed  $existingChecklist
     * @param  array{lead:string,slack_channel_id:string,template_sections:array<int,string>}  $workflow
     * @return array<string,mixed>
     */
    protected function mergeNewsletterWorkflow(mixed $existingChecklist, array $workflow): array
    {
        $checklist = is_array($existingChecklist) ? $existingChecklist : [];
        $currentWorkflow = [];
        if (isset($checklist['workflow']) && is_array($checklist['workflow'])) {
            $currentWorkflow = $checklist['workflow'];
        }

        $currentWorkflow['lead'] = trim((string) ($workflow['lead'] ?? $currentWorkflow['lead'] ?? ''));
        $currentWorkflow['slack_channel_id'] = trim((string) ($workflow['slack_channel_id'] ?? $currentWorkflow['slack_channel_id'] ?? ''));
        $currentWorkflow['template_sections'] = array_values(array_filter(array_map(
            static fn ($section): string => trim((string) $section),
            (array) ($workflow['template_sections'] ?? $currentWorkflow['template_sections'] ?? [])
        )));

        $checklist['workflow'] = $currentWorkflow;

        return $checklist;
    }

    protected function resetCampaignForm(): void
    {
        $this->campaignForm['name'] = '';
        $this->campaignForm['newsletter_id'] = '';
        $this->campaignForm['project_id'] = '';
        $this->campaignForm['subject'] = '';
        $this->campaignForm['preheader'] = '';
        $this->campaignForm['body_text'] = '';
        $this->campaignForm['scheduled_for'] = '';
        $this->campaignForm['manual_recipients'] = '';
    }

    protected function resetAutomationForm(): void
    {
        $this->automationForm['name'] = '';
        $this->automationForm['newsletter_id'] = '';
        $this->automationForm['project_id'] = '';
        $this->automationForm['prompt'] = '';
        $this->automationForm['subject'] = '';
        $this->automationForm['body_text'] = '';
        $this->automationForm['manual_recipients'] = '';
    }

    protected function normalizeTab(): void
    {
        if (! in_array($this->tab, ['newsletters', 'campaigns', 'automations', 'substack', 'activity'], true)) {
            $this->tab = 'newsletters';
        }
    }

    public function render()
    {
        $userId = (int) Auth::id();

        $newsletters = collect();
        $campaigns = collect();
        $automations = collect();
        $substackConnection = null;
        $activityLogs = collect();

        if ($this->migrationReady) {
            $newsletters = $this->safeQuery(
                fn () => OutreachNewsletter::query()
                    ->where('user_id', $userId)
                    ->with('project:id,name')
                    ->latest('updated_at')
                    ->limit(25)
                    ->get(),
                'newsletter data'
            );

            $campaigns = $this->safeQuery(
                fn () => OutreachCampaign::query()
                    ->where('user_id', $userId)
                    ->with(['newsletter:id,name', 'project:id,name'])
                    ->latest('created_at')
                    ->limit(50)
                    ->get(),
                'campaign data'
            );

            $automations = $this->safeQuery(
                fn () => OutreachAutomation::query()
                    ->where('user_id', $userId)
                    ->with(['newsletter:id,name', 'project:id,name'])
                    ->latest('updated_at')
                    ->limit(50)
                    ->get(),
                'automation data'
            );

            $substackConnection = $this->safeQuery(
                fn () => OutreachSubstackConnection::query()
                    ->where('user_id', $userId)
                    ->first(),
                'Substack connection',
                null
            );

            $activityLogs = $this->safeQuery(
                fn () => OutreachActivityLog::query()
                    ->where('user_id', $userId)
                    ->with(['campaign:id,name', 'newsletter:id,name', 'automation:id,name'])
                    ->latest('created_at')
                    ->limit(100)
                    ->get(),
                'activity log'
            );
        }

        return view('livewire.communications.outreach-index', [
            'newsletters' => $newsletters,
            'campaigns' => $campaigns,
            'automations' => $automations,
            'substackConnection' => $substackConnection,
            'activityLogs' => $activityLogs,
            'substackPresets' => $this->substackPresets,
            'substackDraftPreview' => $this->substackDraftPreview,
            'slackConfigured' => trim((string) (
                config('services.slack.bot_token')
                ?: config('services.slack.notifications.bot_user_oauth_token')
            )) !== '',
        ]);
    }

    protected function hasOutreachSchema(): bool
    {
        return Schema::hasTable('outreach_newsletters')
            && Schema::hasTable('outreach_campaigns')
            && Schema::hasTable('outreach_campaign_recipients')
            && Schema::hasTable('outreach_automations')
            && Schema::hasTable('outreach_substack_connections')
            && Schema::hasTable('outreach_activity_logs');
    }

    /**
     * @template T
     *
     * @param  callable():T  $query
     * @param  T|null  $fallback
     * @return T
     */
    protected function safeQuery(callable $query, string $label, mixed $fallback = null): mixed
    {
        try {
            return $query();
        } catch (\Throwable $exception) {
            report($exception);
            if ($this->runtimeError === '') {
                $this->runtimeError = "Outreach {$label} failed to load: ".$exception->getMessage();
            }

            return $fallback ?? collect();
        }
    }
}
