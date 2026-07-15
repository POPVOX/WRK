<?php

use App\Livewire\CongressionalDirectory\StaffShow;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachEmailSuppression;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailEligibilityService;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use Livewire\Livewire;

function congressionalEmailProfile(string $name): CongressionalStaffProfile
{
    return CongressionalStaffProfile::query()->create([
        'profile_key' => hash('sha256', 'email-evidence|'.$name),
        'chamber' => 'House',
        'display_name' => $name,
        'normalized_name' => strtoupper($name),
        'identity_hint' => preg_replace('/[^A-Z]/', '', strtoupper($name)),
        'status' => 'reported_active',
        'review_status' => 'provisional',
        'first_seen_at' => '2025-01-01',
        'last_seen_at' => '2026-03-31',
        'latest_period_end' => '2026-03-31',
    ]);
}

test('guessed emails remain limited until strong evidence confirms the address', function () {
    $profile = congressionalEmailProfile('Alex Evidence');
    $evidence = app(CongressionalEmailEvidenceService::class);
    $eligibility = app(CongressionalEmailEligibilityService::class);
    $staffEmail = $evidence->addAddress($profile, 'Alex.Evidence@house.gov', 'guessed');

    expect($staffEmail->email_normalized)->toBe('alex.evidence@house.gov')
        ->and($eligibility->evaluate($staffEmail))->toMatchArray([
            'tier' => 'limited',
            'campaign_eligible' => false,
            'provisional_test_eligible' => true,
        ]);

    $evidence->recordEvent($staffEmail, 'click', eventKey: hash('sha256', 'click-1'));
    expect($eligibility->evaluate($staffEmail->fresh())['tier'])->toBe('limited');

    $promoted = $evidence->addAddress(
        $profile,
        'alex.evidence@house.gov',
        'sourced',
        sourceUrl: 'https://example.house.gov/staff'
    );
    expect($promoted->source_type)->toBe('sourced')
        ->and($promoted->verification_status)->toBe('sourced')
        ->and($eligibility->evaluate($promoted)['tier'])->toBe('eligible');

    $evidence->recordEvent($promoted, 'human_reply', eventKey: hash('sha256', 'reply-1'));
    expect($staffEmail->fresh()->verification_status)->toBe('replied')
        ->and($eligibility->evaluate($staffEmail->fresh()))->toMatchArray([
            'tier' => 'eligible',
            'campaign_eligible' => true,
        ]);
});

test('hard bounce evidence creates a global idempotent suppression', function () {
    $evidence = app(CongressionalEmailEvidenceService::class);
    $eligibility = app(CongressionalEmailEligibilityService::class);
    $first = $evidence->addAddress(congressionalEmailProfile('First Staffer'), 'shared.office@senate.gov', 'observed');
    $second = $evidence->addAddress(congressionalEmailProfile('Second Staffer'), 'SHARED.OFFICE@senate.gov', 'sourced');
    $eventKey = hash('sha256', 'hard-bounce-shared-office');

    $evidence->recordEvent($first, 'hard_bounce', eventKey: $eventKey);
    $evidence->recordEvent($first, 'hard_bounce', eventKey: $eventKey);

    expect(OutreachEmailSuppression::query()->count())->toBe(1)
        ->and(CongressionalStaffEmailEvent::query()->where('event_key', $eventKey)->count())->toBe(1)
        ->and($eligibility->evaluate($first->fresh())['tier'])->toBe('blocked')
        ->and($eligibility->evaluate($second->fresh())['tier'])->toBe('blocked');
});

test('staff profile records and manages email evidence without sending', function () {
    config()->set('features.congressional_directory_ui', true);
    $user = User::factory()->create();
    $profile = congressionalEmailProfile('Dana Manual');

    $component = Livewire::actingAs($user)
        ->test(StaffShow::class, ['profile' => $profile])
        ->set('emailAddress', 'dana.manual@mail.house.gov')
        ->set('emailSourceType', 'guessed')
        ->set('emailSourceNotes', 'Pattern inferred for a cautious test.')
        ->call('addEmail')
        ->assertSee('dana.manual@mail.house.gov')
        ->assertSee('Limited');

    $staffEmail = $profile->emails()->sole();
    $component->call('suppressEmail', $staffEmail->id)->assertSee('Blocked');

    expect(OutreachEmailSuppression::query()->where('email_normalized', $staffEmail->email_normalized)->exists())->toBeTrue();

    $component->call('restoreEmail', $staffEmail->id)->assertSee('Limited');

    expect(OutreachEmailSuppression::query()->where('email_normalized', $staffEmail->email_normalized)->exists())->toBeFalse();
});
