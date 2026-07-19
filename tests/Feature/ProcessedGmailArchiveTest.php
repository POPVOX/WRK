<?php

use App\Models\Agent;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\CongressionalStaffProfile;
use App\Models\GmailMessage;
use App\Models\User;
use App\Services\Agents\AgentCredentialService;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use App\Services\EmailContentFormatter;
use App\Services\GoogleGmailService;

function processedGmailArchiveFixture(): array
{
    $user = User::factory()->create();
    $profile = CongressionalStaffProfile::query()->create([
        'profile_key' => hash('sha256', 'processed-gmail-profile'),
        'chamber' => 'House',
        'display_name' => 'Former Staffer',
        'normalized_name' => 'FORMER STAFFER',
        'identity_hint' => 'FORMERSTAFFER',
        'status' => CongressionalStaffProfile::STATUS_INACTIVE,
        'review_status' => 'provisional',
    ]);
    $staffEmail = CongressionalStaffEmail::query()->create([
        'profile_id' => $profile->id,
        'email' => 'former.staffer@mail.house.gov',
        'email_normalized' => 'former.staffer@mail.house.gov',
        'source_type' => 'observed',
        'verification_status' => 'departed',
        'is_primary' => true,
    ]);
    $message = GmailMessage::query()->create([
        'user_id' => $user->id,
        'gmail_message_id' => 'processed-message-1',
        'gmail_thread_id' => 'processed-thread-1',
        'subject' => 'Automatic reply',
        'snippet' => 'I am no longer with this office.',
        'from_email' => $staffEmail->email_normalized,
        'to_emails' => [$user->email],
        'sent_at' => now(),
        'is_inbound' => true,
        'labels' => ['INBOX', 'UNREAD', 'IMPORTANT'],
    ]);
    $signal = CongressionalStaffChangeSignal::query()->create([
        'gmail_message_id' => $message->id,
        'user_id' => $user->id,
        'signal_key' => hash('sha256', 'processed-signal-1'),
        'signal_type' => 'departure',
        'status' => 'accepted',
        'source_email' => $staffEmail->email_normalized,
        'target_emails' => [$staffEmail->email_normalized],
        'replacement_contacts' => [],
        'summary' => 'Former staffer left the office.',
        'detected_at' => now(),
        'reviewed_at' => now(),
    ]);
    CongressionalStaffEmailEvent::query()->create([
        'staff_email_id' => $staffEmail->id,
        'user_id' => $user->id,
        'gmail_message_id' => $message->id,
        'event_key' => hash('sha256', 'processed-event-1'),
        'event_type' => 'departure_auto_reply',
        'evidence_strength' => 'medium',
        'metadata' => ['change_signal_id' => $signal->id],
        'occurred_at' => now(),
    ]);

    return compact('user', 'message', 'signal');
}

function testableProcessedGmailService(bool $connected = true, bool $archiveSucceeds = true): GoogleGmailService
{
    return new class(app(AgentCredentialService::class), app(CongressionalStaffChangeDetector::class), app(EmailContentFormatter::class), $connected, $archiveSucceeds) extends GoogleGmailService
    {
        public array $archived = [];

        public function __construct(
            AgentCredentialService $credentials,
            CongressionalStaffChangeDetector $detector,
            EmailContentFormatter $formatter,
            protected bool $connected,
            protected bool $archiveSucceeds
        ) {
            parent::__construct($credentials, $detector, $formatter);
        }

        public function isConnected(User $user, ?Agent $agent = null): bool
        {
            return $this->connected;
        }

        public function archiveThread(User $user, string $threadId, ?Agent $agent = null): array
        {
            if (! $this->archiveSucceeds) {
                throw new RuntimeException('Insufficient Permission: missing gmail.modify scope.');
            }

            $this->archived[] = [$user->id, $threadId];

            return ['thread_id' => $threadId, 'labels' => ['IMPORTANT']];
        }
    };
}

test('accepted and committed staff-change evidence is marked read and archived', function () {
    ['user' => $user, 'message' => $message, 'signal' => $signal] = processedGmailArchiveFixture();
    $sameThread = GmailMessage::query()->create([
        'user_id' => $user->id,
        'gmail_message_id' => 'processed-message-2',
        'gmail_thread_id' => $message->gmail_thread_id,
        'subject' => 'Original outreach',
        'from_email' => $user->email,
        'to_emails' => ['former.staffer@mail.house.gov'],
        'sent_at' => now()->subMinute(),
        'is_inbound' => false,
        'labels' => ['SENT', 'INBOX'],
    ]);
    $service = testableProcessedGmailService();

    expect($service->archiveProcessedStaffChangeSignal($signal))->toBeTrue()
        ->and($service->archived)->toBe([[$user->id, 'processed-thread-1']])
        ->and($message->fresh()->labels)->toBe(['IMPORTANT'])
        ->and($message->fresh()->automation_processed_at)->not->toBeNull()
        ->and($message->fresh()->automation_disposition)->toBe('archived_staff_change')
        ->and($message->fresh()->automation_error)->toBeNull()
        ->and($sameThread->fresh()->labels)->toBe(['SENT'])
        ->and($sameThread->fresh()->automation_processed_at)->not->toBeNull();

    expect($service->archiveProcessedStaffChangeSignal($signal))->toBeTrue()
        ->and($service->archived)->toHaveCount(1);
});

test('messages remain retryable when Gmail cannot archive them', function () {
    ['message' => $message, 'signal' => $signal] = processedGmailArchiveFixture();

    expect(testableProcessedGmailService(archiveSucceeds: false)
        ->archiveProcessedStaffChangeSignal($signal))->toBeFalse()
        ->and($message->fresh()->automation_processed_at)->toBeNull()
        ->and($message->fresh()->automation_error)->toContain('gmail.modify');
});

test('messages are not archived before accepted evidence is committed', function () {
    ['message' => $message, 'signal' => $signal] = processedGmailArchiveFixture();
    CongressionalStaffEmailEvent::query()->delete();
    $service = testableProcessedGmailService();

    expect($service->archiveProcessedStaffChangeSignal($signal))->toBeFalse()
        ->and($service->archived)->toBe([])
        ->and($message->fresh()->labels)->toBe(['INBOX', 'UNREAD', 'IMPORTANT']);
});

test('Google workspace requests Gmail modify permission for processed-message cleanup', function () {
    expect(config('services.google.workspace_scopes'))
        ->toContain('https://www.googleapis.com/auth/gmail.modify');
});
