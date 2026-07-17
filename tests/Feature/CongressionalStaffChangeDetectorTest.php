<?php

use App\Livewire\CongressionalDirectory\ChangeSignalIndex;
use App\Models\CongressionalOffice;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffProfile;
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

function changeSignalProfile(string $name, string $email): CongressionalStaffEmail
{
    $office = CongressionalOffice::query()->create([
        'office_key' => hash('sha256', 'change-office|'.$name),
        'chamber' => 'Senate',
        'name' => 'Office of Senator Katie Britt',
        'normalized_name' => 'OFFICE OF SENATOR KATIE BRITT',
        'office_type' => 'Member office',
        'is_active' => true,
    ]);
    $profile = CongressionalStaffProfile::query()->create([
        'profile_key' => hash('sha256', 'change-profile|'.$name),
        'chamber' => 'Senate',
        'display_name' => $name,
        'normalized_name' => strtoupper($name),
        'identity_hint' => preg_replace('/[^A-Z0-9]/', '', strtoupper($name)),
        'status' => 'reported_active',
        'review_status' => 'provisional',
    ]);
    CongressionalPosition::query()->create([
        'profile_id' => $profile->id,
        'office_id' => $office->id,
        'position_key' => hash('sha256', 'change-position|'.$name),
        'title' => 'Legislative Assistant',
        'normalized_title' => 'LEGISLATIVE ASSISTANT',
        'is_current' => true,
        'confidence' => 'reported',
    ]);

    return app(CongressionalEmailEvidenceService::class)->addAddress($profile, $email, 'observed');
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
        ->and($detector->detect($failure)?->replacement_contacts)->toBe([])
        ->and($detector->detect($ordinary))->toBeNull()
        ->and(CongressionalStaffChangeSignal::query()->count())->toBe(1);
});

test('rescanning preserves reviewed signal decisions', function () {
    $detector = app(CongressionalStaffChangeDetector::class);
    $message = changeSignalMessage();
    $signal = $detector->detect($message);
    $signal->update(['status' => 'rejected', 'reviewed_at' => now()]);

    expect($detector->detect($message)?->status)->toBe('rejected');
});

test('outbound messages never create staff-change signals', function () {
    $message = changeSignalMessage(['is_inbound' => false]);

    expect(app(CongressionalStaffChangeDetector::class)->detect($message))->toBeNull();
});

test('team members can confirm departure evidence and safely add person replacements', function () {
    config()->set('features.congressional_directory_ui', true);
    $reviewer = User::factory()->create();
    changeSignalProfile('Caroline Moore', 'caroline.moore@britt.senate.gov');
    $signal = app(CongressionalStaffChangeDetector::class)->detect(changeSignalMessage());

    Livewire::actingAs($reviewer)
        ->test(ChangeSignalIndex::class)
        ->assertSee('abigail_avery@britt.senate.gov')
        ->call('review', $signal->id, 'accepted');

    expect($signal->fresh()->status)->toBe('accepted')
        ->and($signal->fresh()->reviewed_by)->toBe($reviewer->id)
        ->and($signal->fresh()->reviewed_at)->not->toBeNull()
        ->and(CongressionalStaffEmail::query()->where('email_normalized', 'abigail_avery@britt.senate.gov')->exists())->toBeTrue()
        ->and(CongressionalStaffEmail::query()->where('email_normalized', 'johnhenry_woods@britt.senate.gov')->exists())->toBeTrue()
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

    expect($signal->status)->toBe('accepted')
        ->and($signal->reviewed_at)->not->toBeNull()
        ->and($signal->replacement_contacts)->toBe([])
        ->and(CongressionalStaffEmail::query()->find($staffEmail->id)->verification_status)->toBe('hard_bounced')
        ->and(\App\Models\OutreachEmailSuppression::query()
            ->where('email_normalized', 'former.staff@mail.house.gov')
            ->where('reason', 'hard_bounce')
            ->exists())->toBeTrue();
});

test('generic departure redirects are not added as staff profiles', function () {
    config()->set('features.congressional_directory_ui', true);
    $reviewer = User::factory()->create();
    changeSignalProfile('Caroline Moore', 'caroline.moore@britt.senate.gov');
    $signal = app(CongressionalStaffChangeDetector::class)->detect(changeSignalMessage([
        'body_text' => 'I am no longer with the office. Please contact scheduling@britt.senate.gov or Abigail_Avery@britt.senate.gov.',
    ]));

    Livewire::actingAs($reviewer)
        ->test(ChangeSignalIndex::class)
        ->call('review', $signal->id, 'accepted');

    expect(CongressionalStaffEmail::query()->where('email_normalized', 'scheduling@britt.senate.gov')->exists())->toBeFalse()
        ->and(CongressionalStaffEmail::query()->where('email_normalized', 'abigail_avery@britt.senate.gov')->exists())->toBeTrue();
});

test('backfill command scans only messages that may contain staff changes', function () {
    changeSignalMessage([
        'subject' => 'Re: policy briefing',
        'snippet' => 'Thank you, this is helpful.',
        'body_text' => null,
    ]);
    changeSignalMessage();

    $this->artisan('congressional:scan-gmail-changes', ['--limit' => 100])
        ->expectsOutput('Scanned 1 candidate Gmail messages; 1 matched a staff-change pattern.')
        ->assertSuccessful();

    expect(CongressionalStaffChangeSignal::query()->count())->toBe(1);
});
