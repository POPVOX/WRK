<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $activationToken
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $activationUrl = url("/activate/{$this->activationToken}");

        return (new MailMessage)
            ->subject('You\'re Invited to Join WRK - POPVOX Foundation')
            ->greeting("Hello {$notifiable->name}!")
            ->line('You\'ve been invited to join the WRK workspace management system for POPVOX Foundation.')
            ->line('WRK helps our team track projects, meetings, contacts, and collaborate effectively.')
            ->action('Activate Your Account', $activationUrl)
            ->line('Click the button above to set your password and get started.')
            ->line('This link will expire in 7 days.')
            ->salutation('— The POPVOX Foundation Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'activation_token' => $this->activationToken,
        ];
    }
}

