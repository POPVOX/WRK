<?php

namespace App\Jobs;

use App\Models\RequirementReminder;
use App\Models\User;
use App\Notifications\ReportingRequirementReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReportingReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->startOfDay();

        $reminders = RequirementReminder::whereDate('reminder_date', $today)
            ->whereNull('sent_at')
            ->with(['requirement.legislativeReport'])
            ->get();

        if ($reminders->isEmpty()) {
            Log::info('No reporting reminders to send today.');
            return;
        }

        // Get admin users to notify
        $admins = User::where('is_admin', true)->get();

        foreach ($reminders as $reminder) {
            $requirement = $reminder->requirement;

            if (!$requirement || $requirement->status === 'submitted') {
                $reminder->markAsSent();
                continue;
            }

            // Send notification to assigned user, or all admins if unassigned
            $recipients = $requirement->assignedTo
                ? collect([$requirement->assignedTo])
                : $admins;

            foreach ($recipients as $user) {
                try {
                    $user->notify(new ReportingRequirementReminder($requirement, $reminder->days_before_due));
                } catch (\Exception $e) {
                    Log::error('Failed to send reporting reminder', [
                        'user_id' => $user->id,
                        'requirement_id' => $requirement->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $reminder->markAsSent();
        }

        Log::info('Sent ' . $reminders->count() . ' reporting reminders.');
    }
}

