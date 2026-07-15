<?php

use App\Livewire\CongressionalDirectory\ChangeSignalIndex;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmail;
use App\Models\GmailMessage;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use Livewire\Livewire;

function changeSignalMessage(array $overrides = []): GmailMessage
{
    $user = User::factory()->create();

    return GmailMessage::query()->create(array_merge([
        'user_id' => $user->id,
        'gmail_message_id' => 'gmail-'.fake()->uuid(),
        'gmail_thread_id' => 'thread-1',
        'subject' => 'Automatic reply',
        'snippet' => 'Thank you for reaching out.',
        'body_text' => 'As of April 20th, I am no longer with the Office of Senator Katie Britt. For assistance with legislative matters, please reach out to Abigail_Avery@britt.senate.gov and JohnHenry_Woods@britt.senate.gov.',
        'from_email' => 'caroline.moore@britt.senate.gov',
        'from_name' => 'Caroline Moore',
        'to_emails' => [$user->email],
        'sent_at' => now(),
        'is_inbound' => true,
        'labels' => ['INBOX'],
    ], $overrides));
}

test('departure redirects become pending review signals with observed replacements', function () {
    $message = changeSignalMessage();
    $detector = app(CongressionalStaffChangeDetector::class);

    $signal = $detector->detect($message);

    expect($signal)->not->toBeNull()
        ->and($signal->signal_type)->toBe('departure_redirect')
        ->and($signal->status)->toBe('pending')
        ->and($signal->source_email)->toBe('caroline.moore@britt.senate.gov')
        ->and($signal->target_emails)->toBe(['caroline.moore@britt.senate.gov'])
        ->and($signal->replacement_contacts)->toHaveCount(2)
        ->and($signal->replacement_contacts[0])->toMatchArray([
            'email' => 'abigail_avery@britt.senate.gov',
            'verification_status' => 'observed_in_message',
        ])
        ->and(CongressionalStaffChangeSignal::query()->count())->toBe(1);

    $detector->detect($message);
    expect(CongressionalStaffChangeSignal::query()->count())->toBe(1);
});

test('delivery failures and irrelevant messages are distinguished safely', function () {
    $detector = app(CongressionalStaffChangeDetector::class);
    $failure = changeSignalMessage([
        'subject' => 'Delivery Status Notification (Failure)',
        'snippet' => 'Address not found. Your message was not delivered to former.staff@mail.house.gov.',
        'body_text' => null,
        'from_email' => 'mailer-daemon@googlemail.com',
    ]);
    $ordinary = changeSignalMessage([
        'subject' => 'Re: policy briefing',
        'snippet' => 'Thank you, this is helpful.',
        'body_text' => null,
    ]);

    expect($detector->detect($failure)?->signal_type)->toBe('delivery_failure')
        ->and($detector->detect($ordinary))->toBeNull()
        ->and(CongressionalStaffChangeSignal::query()->count())->toBe(1);
});

test('outbound messages never create staff-change signals', function () {
    $message = changeSignalMessage(['is_inbound' => false]);

    expect(app(CongressionalStaffChangeDetector::class)->detect($message))->toBeNull();
});

test('team members can confirm or dismiss evidence without creating contacts', function () {
    config()->set('features.congressional_directory_ui', true);
    $reviewer = User::factory()->create();
    $signal = app(CongressionalStaffChangeDetector::class)->detect(changeSignalMessage());

    Livewire::actingAs($reviewer)
        ->test(ChangeSignalIndex::class)
        ->assertSee('abigail_avery@britt.senate.gov')
        ->call('review', $signal->id, 'accepted');

    expect($signal->fresh()->status)->toBe('accepted')
        ->and($signal->fresh()->reviewed_by)->toBe($reviewer->id)
        ->and($signal->fresh()->reviewed_at)->not->toBeNull()
        ->and(\App\Models\Person::query()->count())->toBe(0);
});

test('accepted delivery failure records a hard bounce for a known congressional address', function () {
    config()->set('features.congressional_directory_ui', true);
    $reviewer = User::factory()->create();
    $profile = \App\Models\CongressionalStaffProfile::query()->create([
        'profile_key' => hash('sha256', 'known-bounce-profile'),
        'chamber' => 'House',
        'display_name' => 'Former Staff',
        'normalized_name' => 'FORMER STAFF',
        'identity_hint' => 'FORMERSTAFF',
        'status' => 'reported_active',
        'review_status' => 'provisional',
    ]);
    $staffEmail = app(CongressionalEmailEvidenceService::class)
        ->addAddress($profile, 'former.staff@mail.house.gov', 'observed');
    $signal = app(CongressionalStaffChangeDetector::class)->detect(changeSignalMessage([
        'subject' => 'Delivery Status Notification (Failure)',
        'snippet' => 'Address not found. Your message was not delivered to former.staff@mail.house.gov.',
        'body_text' => null,
        'from_email' => 'mailer-daemon@googlemail.com',
    ]));

    Livewire::actingAs($reviewer)
        ->test(ChangeSignalIndex::class)
        ->call('review', $signal->id, 'accepted');

    expect(CongressionalStaffEmail::query()->find($staffEmail->id)->verification_status)->toBe('hard_bounced')
        ->and(\App\Models\OutreachEmailSuppression::query()
            ->where('email_normalized', 'former.staff@mail.house.gov')
            ->where('reason', 'hard_bounce')
            ->exists())->toBeTrue();
});
