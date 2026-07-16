<?php

use App\Jobs\EnrichCongressionalContactData;
use App\Livewire\CongressionalDirectory\ContactDataIndex;
use App\Livewire\CongressionalDirectory\StaffIndex;
use App\Livewire\CongressionalDirectory\StaffListCreate;
use App\Livewire\CongressionalDirectory\StaffListsIndex;
use App\Models\CongressionalOffice;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffObservation;
use App\Models\CongressionalStaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
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
    explorerProfile('Bailey Senate', 'Senate', 'COMMITTEE ON TECHNOLOGY', 'Committee', 'COMMUNICATIONS DIRECTOR');
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
        ->set('title', 'communications')
        ->assertSee('Bailey Senate')
        ->set('title', '')
        ->set('search', 'committee on technology')
        ->assertSee('Bailey Senate')
        ->set('search', 'not present')
        ->assertSee('No staff profiles match these filters');
});

test('contact data enrichment is directory wide and queues without sending', function () {
    config()->set('features.congressional_directory_ui', true);
    Cache::forget(EnrichCongressionalContactData::CACHE_KEY);
    Queue::fake();
    $user = User::factory()->create();
    explorerProfile('Directory Enrichment', 'House', 'Office of Representative Example', 'Member office', 'STAFF ASSISTANT');

    Livewire::actingAs($user)
        ->test(ContactDataIndex::class)
        ->assertSee('Contact data')
        ->assertSeeText('1 ready')
        ->set('instructions', 'Reviewed House and Senate conventions.')
        ->call('generateEmailGuesses')
        ->assertHasNoErrors()
        ->assertSee('Enrichment queued');

    Queue::assertPushed(
        EnrichCongressionalContactData::class,
        fn (EnrichCongressionalContactData $job) => $job->userId === $user->id
            && $job->instructions === 'Reviewed House and Senate conventions.'
    );
    Cache::forget(EnrichCongressionalContactData::CACHE_KEY);
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

test('staff can build a saved list from checked profiles or all filtered matches', function () {
    config()->set('features.congressional_directory_ui', true);
    $user = User::factory()->create();
    $caseworker = explorerProfile('Alex Caseworker', 'House', 'Office of Representative Alpha', 'Member office', 'CASEWORKER');
    $legislativeAssistant = explorerProfile('Blair Assistant', 'House', 'Office of Representative Beta', 'Member office', 'LEGISLATIVE ASSISTANT');
    $formerCaseworker = explorerProfile('Chris Former', 'Senate', 'Office of Senator Former', 'Member office', 'CASEWORKER', false);

    Livewire::actingAs($user)
        ->test(StaffIndex::class)
        ->set('newListName', 'District staff')
        ->call('createList')
        ->set('checkedProfileIds', [$legislativeAssistant->id])
        ->call('addCheckedToList')
        ->set('title', 'caseworker')
        ->call('addAllMatchesToList');

    $list = CongressionalStaffList::query()->where('user_id', $user->id)->sole();

    expect($list->profiles()->pluck('congressional_staff_profiles.id')->all())
        ->toContain($caseworker->id, $legislativeAssistant->id)
        ->not->toContain($formerCaseworker->id);
});

test('staff can review lists and remove individual members without deleting profiles', function () {
    config()->set('features.congressional_directory_ui', true);
    $user = User::factory()->create();
    $profile = explorerProfile('Dana Remove', 'Senate', 'Committee on Lists', 'Committee', 'COUNSEL');
    $list = CongressionalStaffList::query()->create([
        'user_id' => $user->id,
        'name' => 'Review list',
    ]);
    $list->profiles()->attach($profile->id, ['added_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(StaffListsIndex::class)
        ->assertSee('Dana Remove')
        ->call('removeFromList', $profile->id)
        ->assertDontSee('Dana Remove');

    expect($list->profiles()->count())->toBe(0)
        ->and(CongressionalStaffProfile::query()->whereKey($profile->id)->exists())->toBeTrue();
});

test('guided list builder saves criteria and either selected or all matching staff', function () {
    config()->set('features.congressional_directory_ui', true);
    $user = User::factory()->create();
    $caseworker = explorerProfile('Guided Caseworker', 'House', 'Office of Representative Guided', 'Member office', 'CASEWORKER');
    explorerProfile('Guided Counsel', 'Senate', 'Committee on Guided', 'Committee', 'COUNSEL');

    Livewire::actingAs($user)
        ->test(StaffListCreate::class)
        ->set('name', 'Guided district staff')
        ->set('description', 'Current House caseworkers')
        ->set('chamber', 'House')
        ->set('title', 'caseworker')
        ->call('runSearch')
        ->assertSee('Guided Caseworker')
        ->assertDontSee('Guided Counsel')
        ->call('selectAllMatches')
        ->call('saveList')
        ->assertRedirect();

    $list = CongressionalStaffList::query()->where('user_id', $user->id)->sole();

    expect($list->selection_mode)->toBe('filtered_snapshot')
        ->and($list->criteria)->toMatchArray(['chamber' => 'House', 'title' => 'caseworker'])
        ->and($list->profiles()->sole()->is($caseworker))->toBeTrue();
});
