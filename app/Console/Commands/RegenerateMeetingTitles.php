<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RegenerateMeetingTitles extends Command
{
    protected $signature = 'meetings:regenerate-titles 
                            {--user= : Only process meetings for a specific user ID}
                            {--all : Process all meetings, not just those without titles}
                            {--dry-run : Show what would be changed without saving}';

    protected $description = 'Regenerate meeting titles from notes and attendee data';

    public function handle(): int
    {
        $query = Meeting::with('people');

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        if (!$this->option('all')) {
            // Only process meetings with empty/generic titles
            $query->where(function ($q) {
                $q->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhere('title', 'like', 'Meeting:%')
                    ->orWhere('title', 'like', 'Untitled%');
            });
        }

        $meetings = $query->get();

        $this->info("Found {$meetings->count()} meetings to process");

        $updated = 0;
        $skipped = 0;

        foreach ($meetings as $meeting) {
            $oldTitle = $meeting->title ?? '(empty)';
            $newTitle = $this->generateTitle($meeting);

            if ($oldTitle === $newTitle) {
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [{$meeting->id}] \"{$oldTitle}\" → \"{$newTitle}\"");
            } else {
                $meeting->update(['title' => $newTitle]);
            }

            $updated++;
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run: {$updated} meetings would be updated, {$skipped} skipped");
        } else {
            $this->info("Updated {$updated} meeting titles, {$skipped} skipped");
        }

        return 0;
    }

    protected function generateTitle(Meeting $meeting): string
    {
        $rawTitle = $meeting->title;
        $description = $meeting->raw_notes ?? '';
        $date = $meeting->meeting_date;

        // If we have a good existing title that's not generic, keep it
        if ($rawTitle && !preg_match('/^\d{1,2}\/\d{1,2}|^\w+day|^meeting:|^meeting$|^untitled/i', $rawTitle)) {
            return $rawTitle;
        }

        // Try to extract a meaningful title from the notes
        if ($description) {
            // Look for lines starting with ** which often contain the meeting focus
            if (preg_match('/\*\*(.+?)\*\*/', $description, $matches)) {
                $extracted = trim($matches[1]);
                if (strlen($extracted) > 5 && strlen($extracted) < 100) {
                    return $extracted;
                }
            }

            // Use first non-empty line of description if short enough
            $lines = explode("\n", $description);
            foreach ($lines as $line) {
                $line = trim(strip_tags($line));
                // Skip empty lines and markdown headers
                if (strlen($line) > 5 && strlen($line) < 80 && !str_starts_with($line, '#')) {
                    return $line;
                }
            }
        }

        // Build title from attendee names
        $attendees = $meeting->people;
        if ($attendees->isNotEmpty()) {
            $names = $attendees->take(3)->pluck('name')->toArray();
            $nameList = implode(', ', $names);
            if ($attendees->count() > 3) {
                $nameList .= ' +' . ($attendees->count() - 3);
            }
            return "Meeting with {$nameList}";
        }

        // Fall back to date-based title
        return "Meeting: " . $date->format('M j, Y');
    }
}
