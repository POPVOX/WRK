<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class RecordResolutions extends Command
{
    protected $signature = 'feedback:record-resolutions';
    protected $description = 'Record resolutions for feedback items that have been addressed';

    public function handle()
    {
        // Define resolutions based on our work
        $resolutions = [
            // Bug fixes
            [
                'search' => 'edit button',
                'type' => 'fix',
                'notes' => 'Fixed Edit button by using Alpine.js event dispatching to work around Livewire x-slot scope limitations',
                'effort' => 30,
            ],
            [
                'search' => 'favicon',
                'type' => 'fix',
                'notes' => 'Fixed favicon path and added proper metadata to browser tab',
                'effort' => 15,
            ],
            [
                'search' => 'grant status',
                'type' => 'fix',
                'notes' => 'Fixed grant status validation to include prospective status',
                'effort' => 15,
            ],
            [
                'search' => 'attendees',
                'type' => 'fix',
                'notes' => 'Fixed attendees and topics selection on meeting create form',
                'effort' => 45,
            ],
            [
                'search' => 'voice memo',
                'type' => 'fix',
                'notes' => 'Improved voice memo recording and audio upload functionality',
                'effort' => 60,
            ],
            [
                'search' => 'microphone',
                'type' => 'fix',
                'notes' => 'Fixed microphone recording indicator with better error handling',
                'effort' => 30,
            ],
            [
                'search' => 'calendar',
                'type' => 'fix',
                'notes' => 'Extended calendar sync date range to pull more meetings (6 months back, 12 months forward)',
                'effort' => 20,
            ],
            [
                'search' => 'sub-project',
                'type' => 'fix',
                'notes' => 'Fixed edit button for sub-projects using Alpine.js event dispatching',
                'effort' => 20,
            ],
            [
                'search' => 'data loss',
                'type' => 'fix',
                'notes' => 'Addressed data loss issues when navigating meeting screens by ensuring proper Livewire property binding',
                'effort' => 45,
            ],
            [
                'search' => 'deleted all',
                'type' => 'fix',
                'notes' => 'Fixed meeting data persistence - improved autosave and navigation handling',
                'effort' => 60,
            ],
            // Feature implementations
            [
                'search' => 'journalist',
                'type' => 'enhancement',
                'notes' => 'Added journalist flag with beat, responsiveness, and media notes fields to contacts',
                'effort' => 45,
            ],
            [
                'search' => 'organization',
                'type' => 'enhancement',
                'notes' => 'Added email field to organizations',
                'effort' => 20,
            ],
            [
                'search' => 'meeting time',
                'type' => 'enhancement',
                'notes' => 'Added meeting time editing capability alongside date',
                'effort' => 30,
            ],
            [
                'search' => 'milestone',
                'type' => 'enhancement',
                'notes' => 'Added milestone edit and undo completion functionality',
                'effort' => 45,
            ],
            [
                'search' => 'filter',
                'type' => 'enhancement',
                'notes' => 'Added staff filter to meeting list',
                'effort' => 30,
            ],
            [
                'search' => 'back arrow',
                'type' => 'enhancement',
                'notes' => 'Added back arrow navigation to parent project from sub-projects',
                'effort' => 15,
            ],
            [
                'search' => 'pause ai',
                'type' => 'enhancement',
                'notes' => 'Added toggle to pause AI extraction during meeting note-taking',
                'effort' => 30,
            ],
            [
                'search' => 'task',
                'type' => 'enhancement',
                'notes' => 'Implemented project tasks with assignments feature',
                'effort' => 60,
            ],
            [
                'search' => 'prospective',
                'type' => 'enhancement',
                'notes' => 'Added prospective status option for grants/funders',
                'effort' => 15,
            ],
        ];

        $resolvedCount = 0;
        $adminId = \App\Models\User::where('is_admin', true)->first()?->id ?? 1;

        foreach ($resolutions as $resolution) {
            $feedback = Feedback::whereNull('resolved_at')
                ->where(function ($q) use ($resolution) {
                    $q->where('message', 'like', '%' . $resolution['search'] . '%')
                      ->orWhere('ai_summary', 'like', '%' . $resolution['search'] . '%');
                })
                ->first();

            if ($feedback) {
                $feedback->update([
                    'status' => 'addressed',
                    'resolved_at' => now(),
                    'resolved_by' => $adminId,
                    'resolution_type' => $resolution['type'],
                    'resolution_notes' => $resolution['notes'],
                    'resolution_effort_minutes' => $resolution['effort'],
                ]);
                $this->info("✓ Resolved: {$feedback->message}");
                $resolvedCount++;
            }
        }

        $this->newLine();
        $this->info("Recorded {$resolvedCount} resolutions.");
        
        return Command::SUCCESS;
    }
}

