<?php

namespace App\Console\Commands;

use App\Models\GmailMessage;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ScanCongressionalGmailChanges extends Command
{
    protected $signature = 'congressional:scan-gmail-changes {--limit=5000 : Maximum inbound messages to scan}';

    protected $description = 'Create reviewable congressional staff-change signals from imported Gmail messages';

    public function handle(CongressionalStaffChangeDetector $detector): int
    {
        if (! Schema::hasTable('congressional_staff_change_signals')) {
            $this->error('Congressional change-signal tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 50000));
        $scanned = 0;
        $signals = 0;
        $patterns = [
            '%no longer with%',
            '%no longer at%',
            '%left the office%',
            '%has left%',
            '%departed%',
            '%my last day%',
            '%delivery status notification%',
            '%address not found%',
            '%not delivered%',
            '%undeliverable%',
            '%recipient address rejected%',
            '%550 5.%',
        ];

        GmailMessage::query()
            ->where('is_inbound', true)
            ->where(function ($query) use ($patterns): void {
                $searchableText = "LOWER(COALESCE(subject, '') || ' ' || COALESCE(snippet, '') || ' ' || COALESCE(body_text, ''))";
                foreach ($patterns as $pattern) {
                    $query->orWhereRaw("{$searchableText} LIKE ?", [$pattern]);
                }
            })
            ->latest('sent_at')
            ->limit($limit)
            ->each(function (GmailMessage $message) use ($detector, &$scanned, &$signals): void {
                $scanned++;
                if ($detector->detect($message)) {
                    $signals++;
                }
            });

        $this->info("Scanned {$scanned} candidate Gmail messages; {$signals} matched a staff-change pattern.");

        return self::SUCCESS;
    }
}
