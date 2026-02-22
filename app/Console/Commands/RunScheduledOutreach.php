<?php

namespace App\Console\Commands;

use App\Jobs\QueueOutreachCampaign;
use App\Models\OutreachCampaign;
use App\Services\Outreach\OutreachAutomationService;
use Illuminate\Console\Command;

class RunScheduledOutreach extends Command
{
    protected $signature = 'outreach:run-scheduled
        {--sync : Run queue work inline for due campaigns}
        {--limit=25 : Max due campaigns to process}';

    protected $description = 'Queue due outreach campaigns and execute due outreach automations.';

    public function handle(OutreachAutomationService $automationService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 200));
        $runInline = (bool) $this->option('sync');

        $dueCampaigns = OutreachCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->get();

        $campaignsProcessed = 0;
        foreach ($dueCampaigns as $campaign) {
            try {
                if ($runInline) {
                    QueueOutreachCampaign::dispatchSync((int) $campaign->id);
                } else {
                    QueueOutreachCampaign::dispatch((int) $campaign->id);
                }
                $campaignsProcessed++;
            } catch (\Throwable $exception) {
                $this->warn("Campaign {$campaign->id} skipped: {$exception->getMessage()}");
            }
        }

        $automationsProcessed = $automationService->runDueAutomations($limit);

        $this->info("Outreach scheduler complete. Due campaigns queued: {$campaignsProcessed}. Automations executed: {$automationsProcessed}.");

        return self::SUCCESS;
    }
}
