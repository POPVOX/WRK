<?php

namespace App\Notifications;

use App\Models\ReportingRequirement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportingRequirementReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ReportingRequirement $requirement,
        public int $daysBefore
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->requirement->due_date?->format('F j, Y') ?? 'TBD';
        $urgency = $this->daysBefore <= 7 ? 'URGENT: ' : '';

        return (new MailMessage)
            ->subject("{$urgency}Reporting Requirement Due in {$this->daysBefore} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that a reporting requirement is due soon:")
            ->line("**{$this->requirement->report_title}**")
            ->line("**Agency:** {$this->requirement->responsible_agency}")
            ->line("**Due Date:** {$dueDate}")
            ->line("**Days Remaining:** {$this->daysBefore}")
            ->action('View Requirement', url(route('appropriations.index')))
            ->line('Please ensure this requirement is addressed before the deadline.')
            ->salutation('— WRK Appropriations Tracker');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reporting_requirement_reminder',
            'requirement_id' => $this->requirement->id,
            'title' => $this->requirement->report_title,
            'agency' => $this->requirement->responsible_agency,
            'due_date' => $this->requirement->due_date?->toDateString(),
            'days_before' => $this->daysBefore,
        ];
    }
}

