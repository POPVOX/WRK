<?php

namespace App\Console\Commands;

use App\Models\GmailMessage;
use App\Models\OutreachCampaignRecipient;
use App\Services\ContactActivityService;
use Illuminate\Console\Command;

class BackfillContactActivities extends Command
{
    protected $signature = 'contacts:backfill-activities {--force : Write activity records instead of reporting counts}';

    protected $description = 'Backfill contact timelines from previously sent outreach and synced Gmail messages.';

    public function handle(ContactActivityService $activities): int
    {
        $sentCount = OutreachCampaignRecipient::query()->whereNotNull('sent_at')->count();
        $gmailCount = GmailMessage::query()->count();

        if (! $this->option('force')) {
            $this->info("Would inspect {$sentCount} sent outreach recipients and {$gmailCount} Gmail messages.");
            $this->comment('Run again with --force to write idempotent activity records.');

            return self::SUCCESS;
        }

        $this->withProgressBar(
            OutreachCampaignRecipient::query()->whereNotNull('sent_at')->lazyById(),
            fn (OutreachCampaignRecipient $recipient) => $activities->recordCampaignSend($recipient)
        );
        $this->newLine();

        $this->withProgressBar(
            GmailMessage::query()->with('user')->lazyById(),
            fn (GmailMessage $message) => $activities->recordGmailMessage($message)
        );
        $this->newLine();
        $this->info('Contact activities backfilled. Existing source records were left unchanged.');

        return self::SUCCESS;
    }
}
