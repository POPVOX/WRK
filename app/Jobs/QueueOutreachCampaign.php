<?php

namespace App\Jobs;

use App\Models\OutreachCampaign;
use App\Services\Outreach\OutreachCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueOutreachCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $campaignId) {}

    public function handle(OutreachCampaignService $campaignService): void
    {
        $campaign = OutreachCampaign::query()->find($this->campaignId);
        if (! $campaign) {
            return;
        }

        $campaignService->queueCampaign($campaign);
    }
}

