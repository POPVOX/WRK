<?php

use App\Livewire\CongressionalDirectory\StaffIndex;
use App\Models\CongressionalOffice;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffObservation;
use App\Models\CongressionalStaffProfile;
use App\Models\User;
use Livewire\Livewire;

function explorerProfile(string $name, string $chamber, string $officeName, string $officeType, string $title, bool $current = true): CongressionalStaffProfile
{
    $suffix = hash('sha256', implode('|', [$name, $chamber, $officeName, $title]));
    $office = CongressionalOffice::query()->create([
        'office_key' => hash('sha256', $chamber.'|'.$officeName),
        'chamber' => $chamber,
        'name' => $officeName,
        'normalized_name' => strtoupper($officeName),
        'office_code' => strtoupper(substr($suffix, 0, 6)),
        'office_type' => $officeType,
        'is_active' => $current,
    ]);
    $profile = CongressionalStaffProfile::query()->create([
        'profile_key' => $suffix,
        'chamber' => $chamber,
        'display_name' => $name,
        'normalized_name' => strtoupper($name),
        'identity_hint' => preg_replace('/[^A-Z]/', '', strtoupper($name)),
        'status' => 'reported',
        'review_status' => 'provisional',
        'first_seen_at' => '2025-01-01',
        'last_seen_at' => '2026-03-31',
        'latest_period_end' => '2026-03-31',
    ]);
    $position = CongressionalPosition::query()->create([
        'profile_id' => $profile->id,
        'office_id' => $office->id,
        'position_key' => hash('sha256', $suffix.'|'.$office->id.'|'.$title),
        'title' => $title,
        'normalized_title' => strtoupper($title),
        'first_reported_start' => '2025-01-01',
        'last_reported_end' => '2026-03-31',
        'is_current' => $current,
        'confidence' => 'reported',
    ]);
    CongressionalStaffObservation::query()->create([
        'profile_id' => $profile->id,
        'office_id' => $office->id,
        'position_id' => $position->id,
        'observation_id' => $chamber.':'.$suffix,
        'source_record_hash' => $suffix,
        'chamber' => $chamber,
        'name_raw' => strtoupper($name),
        'identity_hint' => $profile->identity_hint,
        'office_raw' => $officeName,
        'office_code' => $office->office_code,
        'office_type' => $officeType,
        'title_raw' => $title,
        'period_label' => 'Jan-Mar 2026',
        'period_start' => '2026-01-01',
        'period_end' => '2026-03-31',
        'active_in_latest_report' => $current,
        'source_data' => ['url' => 'https://example.gov/source.csv'],
        'evidence' => ['rowCount' => 1],
    ]);

    return $profile;
}

test('congress explorer is unavailable while its feature flag is off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('congress.index'))
        ->assertNotFound();
});

test('congress explorer searches and filters staff separately from contacts', function () {
    config()->set('features.congressional_directory_ui', true);

    explorerProfile('Avery House', 'House', 'Office of Representative Example', 'Member office', 'Legislative Director');
    explorerProfile('Bailey Senate', 'Senate', 'Committee on Technology', 'Committee', 'Communications Director');
    explorerProfile('Casey Former', 'House', 'Office of Representative Former', 'Member office', 'Policy Adviser', false);

    Livewire::actingAs(User::factory()->create())
        ->test(StaffIndex::class)
        ->assertSee('Avery House')
        ->assertSee('Bailey Senate')
        ->assertDontSee('Casey Former')
        ->set('chamber', 'Senate')
        ->assertSee('Bailey Senate')
        ->assertDontSee('Avery House')
        ->set('officeType', 'Committee')
        ->set('title', 'Communications')
        ->assertSee('Bailey Senate')
        ->set('search', 'not present')
        ->assertSee('No staff profiles match these filters');
});

test('congress staff detail shows role history and source evidence', function () {
    config()->set('features.congressional_directory_ui', true);
    $profile = explorerProfile('Dana Evidence', 'Senate', 'Committee on Evidence', 'Committee', 'Research Director');

    $this->actingAs(User::factory()->create())
        ->get(route('congress.staff.show', $profile))
        ->assertOk()
        ->assertSee('Dana Evidence')
        ->assertSee('Research Director')
        ->assertSee('Committee on Evidence')
        ->assertSee('View source')
        ->assertSee('Not linked to Contacts');
});
