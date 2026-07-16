<?php

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Jobs\GenerateCongressionalEmailGuesses;
use App\Jobs\SendOutreachCampaignRecipient;
use App\Livewire\CongressionalDirectory\CampaignCreate;
use App\Livewire\CongressionalDirectory\CampaignIndex;
use App\Livewire\CongressionalDirectory\OutreachAnalytics;
use App\Livewire\CongressionalDirectory\OutreachDraftShow;
use App\Models\CongressionalOffice;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffObservation;
use App\Models\CongressionalStaffProfile;
use App\Models\ContactActivity;
use App\Models\GmailMessage;
use App\Models\OutreachCampaign;
use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachEmailSuppression;
use App\Models\Person;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalCampaignScheduleService;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use App\Services\CongressionalDirectory\CongressionalOutreachBatchService;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use App\Services\ContactActivityService;
use App\Services\GoogleGmailService;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\OutreachSuppressionService;
use App\Services\Outreach\OutreachTrackingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function workbenchProfile(string $name, bool $current = true): CongressionalStaffProfile
{
    $key = hash('sha256', 'workbench|'.$name);
    $office = CongressionalOffice::query()->create([
        'office_key' => hash('sha256', 'office|'.$name),
        'chamber' => 'House',
        'name' => 'Office of Representative '.$name,
        'normalized_name' => strtoupper('Office of Representative '.$name),
        'office_code' => strtoupper(substr($key, 0, 6)),
        'office_type' => 'Member office',
        'is_active' => $current,
    ]);
    $profile = CongressionalStaffProfile::query()->create([
        'profile_key' => $key,
        'chamber' => 'House',
        'display_name' => $name,
        'normalized_name' => strtoupper($name),
        'identity_hint' => preg_replace('/[^A-Z]/', '', strtoupper($name)),
        'status' => $current ? 'reported_active' : 'historical',
        'review_status' => 'provisional',
        'first_seen_at' => '2025-01-01',
        'last_seen_at' => '2026-03-31',
        'latest_period_end' => '2026-03-31',
    ]);
    CongressionalPosition::query()->create([
        'profile_id' => $profile->id,
        'office_id' => $office->id,
        'position_key' => hash('sha256', 'position|'.$name),
        'title' => 'Legislative Assistant',
        'normalized_title' => 'LEGISLATIVE ASSISTANT',
        'first_reported_start' => '2025-01-01',
        'last_reported_end' => '2026-03-31',
        'is_current' => $current,
        'confidence' => 'reported',
    ]);

    return $profile;
}

function workbenchList(User $user, array $profiles): CongressionalStaffList
{
    $list = CongressionalStaffList::query()->create([
        'user_id' => $user->id,
        'name' => 'Workbench source',
    ]);
    $list->profiles()->attach(
        collect($profiles)->mapWithKeys(fn (CongressionalStaffProfile $profile) => [
            $profile->id => ['added_by' => $user->id],
        ])->all()
    );

    return $list;
}

function builtWorkbenchDraft(
    CongressionalOutreachWorkbenchService $workbench,
    CongressionalStaffList $list,
    User $user,
    string $name
): CongressionalOutreachDraft {
    $draft = $workbench->createDraft($list, $user->id, $name);
    $workbench->refreshSnapshot($draft);

    return $draft->fresh();
}

function addWorkbenchObservation(
    CongressionalStaffProfile $profile,
    string $rawName,
    string $sourceKind
): CongressionalStaffObservation {
    $position = $profile->currentPosition()->with('office')->firstOrFail();

    return CongressionalStaffObservation::query()->create([
        'profile_id' => $profile->id,
        'office_id' => $position->office_id,
        'position_id' => $position->id,
        'observation_id' => strtolower($profile->chamber).':'.hash('sha256', $profile->id.'|'.$rawName.'|'.$sourceKind),
        'source_record_hash' => hash('sha256', $rawName.'|'.$sourceKind),
        'chamber' => $profile->chamber,
        'name_raw' => $rawName,
        'identity_hint' => preg_replace('/[^A-Z0-9]/', '', strtoupper($rawName)),
        'office_raw' => $position->office->name,
        'office_code' => $position->office->office_code,
        'office_type' => 'Member office',
        'title_raw' => $position->title,
        'period_start' => '2026-01-01',
        'period_end' => '2026-03-31',
        'active_in_latest_report' => true,
        'source_data' => ['kind' => $sourceKind],
    ]);
}

test('dry run resolves the safest address and excludes unsafe recipient records', function () {
    $user = User::factory()->create();
    $evidence = app(CongressionalEmailEvidenceService::class);
    $preferred = workbenchProfile('Avery Preferred');
    $noAddress = workbenchProfile('Bailey No Address');
    $inactive = workbenchProfile('Casey Former', false);
    $suppressed = workbenchProfile('Dana Suppressed');
    $duplicateOne = workbenchProfile('Emery Duplicate');
    $duplicateTwo = workbenchProfile('Finley Duplicate');

    $guessed = $evidence->addAddress($preferred, 'avery.guess@house.gov', 'guessed');
    $guessed->update(['is_primary' => true]);
    $sourced = $evidence->addAddress($preferred, 'avery.sourced@house.gov', 'sourced');
    $evidence->addAddress($inactive, 'casey@house.gov', 'sourced');
    $blocked = $evidence->addAddress($suppressed, 'dana@house.gov', 'sourced');
    OutreachEmailSuppression::query()->create([
        'email_normalized' => $blocked->email_normalized,
        'reason' => 'hard_bounce',
        'source_type' => 'test',
        'suppressed_at' => now(),
    ]);
    $evidence->addAddress($duplicateOne, 'shared.office@house.gov', 'sourced');
    $evidence->addAddress($duplicateTwo, 'shared.office@house.gov', 'sourced');

    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($user, [$preferred, $noAddress, $inactive, $suppressed, $duplicateOne, $duplicateTwo]),
        $user,
        'Safety review'
    );

    $preferredRecipient = $draft->recipients()->where('profile_id', $preferred->id)->sole();
    expect($preferredRecipient->staff_email_id)->toBe($sourced->id)
        ->and($preferredRecipient->eligibility_tier)->toBe('eligible')
        ->and($preferredRecipient->review_status)->toBe('pending')
        ->and($draft->recipients()->where('profile_id', $noAddress->id)->sole()->exclusion_reason)->toBe('no_address')
        ->and($draft->recipients()->where('profile_id', $inactive->id)->sole()->exclusion_reason)->toBe('inactive_profile')
        ->and($draft->recipients()->where('profile_id', $suppressed->id)->sole()->exclusion_reason)->toBe('blocked_address')
        ->and($draft->recipients()->where('exclusion_reason', 'duplicate_address')->count())->toBe(1);
});

test('bulk review skips provisional addresses while individual review can approve them', function () {
    $user = User::factory()->create();
    $evidence = app(CongressionalEmailEvidenceService::class);
    $eligibleProfile = workbenchProfile('Grace Eligible');
    $limitedProfile = workbenchProfile('Harper Provisional');
    $evidence->addAddress($eligibleProfile, 'grace@house.gov', 'sourced');
    $evidence->addAddress($limitedProfile, 'harper.guess@house.gov', 'guessed');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($user, [$eligibleProfile, $limitedProfile]),
        $user,
        'Approval review'
    );

    expect($workbench->approveAllEligible($draft, $user->id))->toBe(1);

    $eligible = $draft->recipients()->where('profile_id', $eligibleProfile->id)->sole();
    $limited = $draft->recipients()->where('profile_id', $limitedProfile->id)->sole();
    expect($eligible->review_status)->toBe('approved')
        ->and($limited->review_status)->toBe('pending');

    $workbench->updateMessage(
        $draft,
        'Resource for {{office}}',
        "Hi {{first_name}},\n\nThis may help your work as {{title}}."
    );
    expect(fn () => $workbench->markReady($draft->fresh()))
        ->toThrow(DomainException::class, 'Approve or exclude every pending recipient first.');

    $workbench->approve($limited, $user->id);
    $preview = $workbench->preview($draft->fresh(), $limited->fresh());
    $workbench->markReady($draft->fresh());

    expect($preview['subject'])->toContain('Office of Representative Harper Provisional')
        ->and($preview['body'])->toContain('Hi Harper,')
        ->and($preview['body'])->toContain('Legislative Assistant')
        ->and($draft->fresh()->status)->toBe('ready')
        ->and(OutreachCampaign::query()->count())->toBe(0);
});

test('staff can create and inspect a persistent dry run without sending', function () {
    config()->set('features.congressional_directory_ui', true);
    Queue::fake();
    $user = User::factory()->create();
    $profile = workbenchProfile('Jordan Review');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'jordan@house.gov', 'observed');
    $list = workbenchList($user, [$profile]);

    Livewire::actingAs($user)
        ->test(CampaignCreate::class)
        ->set('staffListId', $list->id)
        ->set('name', 'Resource pilot')
        ->call('createCampaign');

    $draft = CongressionalOutreachDraft::query()->sole();

    expect($draft->status)->toBe('building')
        ->and($draft->recipients()->count())->toBe(0);
    Queue::assertPushed(
        BuildCongressionalOutreachDraftSnapshot::class,
        fn (BuildCongressionalOutreachDraftSnapshot $job) => $job->draftId === $draft->id
    );

    Livewire::actingAs($user)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertSee('Building the recipient snapshot');

    (new BuildCongressionalOutreachDraftSnapshot($draft->id))
        ->handle(app(CongressionalOutreachWorkbenchService::class));
    $draft->refresh();

    Livewire::actingAs($user)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertSee('Controlled Gmail delivery')
        ->assertSee('Jordan Review')
        ->call('approveAllEligible')
        ->set('subject', 'Hello {{first_name}}')
        ->set('bodyText', 'A useful resource for {{office}}.')
        ->call('saveMessage')
        ->assertSee('Personalization preview');

    $this->actingAs(User::factory()->create())
        ->get(route('congress.outreach.show', $draft))
        ->assertNotFound();

    expect(CongressionalOutreachDraftRecipient::query()->sole()->review_status)->toBe('approved')
        ->and(OutreachCampaign::query()->count())->toBe(0);
});

test('campaign owners can grant and revoke view-only access for active team members', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create(['name' => 'Campaign Owner']);
    $viewer = User::factory()->create(['name' => 'Campaign Viewer', 'is_active' => true]);
    $inactive = User::factory()->create(['name' => 'Former Viewer', 'is_active' => false]);
    $profile = workbenchProfile('Morgan Shared');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'morgan@house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($owner, [$profile]),
        $owner,
        'Shared resource campaign'
    );

    $ownerComponent = Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertSee('Campaign viewers')
        ->assertSee('Campaign Viewer')
        ->assertDontSee('Former Viewer')
        ->set('selectedViewerId', $viewer->id)
        ->call('addViewer')
        ->assertHasNoErrors()
        ->assertSee('Campaign Viewer');

    expect($draft->viewers()->whereKey($viewer->id)->exists())->toBeTrue();

    Livewire::actingAs($viewer)
        ->test(OutreachDraftShow::class, ['draft' => $draft->fresh()])
        ->assertSee('View-only campaign shared by Campaign Owner')
        ->assertDontSee('Add viewer')
        ->assertDontSee('Approve all eligible')
        ->assertDontSee('Generate provisional email guesses')
        ->call('approveAllEligible')
        ->assertStatus(403);

    Livewire::actingAs($viewer)
        ->test(CampaignIndex::class)
        ->assertSee('Shared resource campaign');

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft->fresh()])
        ->call('removeViewer', $viewer->id);

    expect($draft->viewers()->whereKey($viewer->id)->exists())->toBeFalse();

    $this->actingAs($viewer)
        ->get(route('congress.outreach.show', $draft))
        ->assertNotFound();
});

test('inactive team members cannot be added as campaign viewers', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create();
    $inactive = User::factory()->create(['is_active' => false]);
    $profile = workbenchProfile('Riley Owner');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'riley@house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($owner, [$profile]),
        $owner,
        'Private campaign'
    );

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->set('selectedViewerId', $inactive->id)
        ->call('addViewer')
        ->assertHasErrors('selectedViewerId');

    expect($draft->viewers()->count())->toBe(0);
});

test('snapshot building uses bounded database work as a staff list grows', function () {
    $user = User::factory()->create();
    $evidence = app(CongressionalEmailEvidenceService::class);
    $profiles = collect(range(1, 75))->map(function (int $index) use ($evidence) {
        $profile = workbenchProfile(sprintf('Scale Test %03d', $index));
        $evidence->addAddress($profile, "scale{$index}@house.gov", 'sourced');

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = $workbench->createDraft(workbenchList($user, $profiles), $user->id, 'Scale review');
    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $count = $workbench->refreshSnapshot($draft);

    expect($count)->toBe(75)
        ->and($draft->fresh()->status)->toBe('draft')
        ->and($draft->recipients()->count())->toBe(75)
        ->and($queryCount)->toBeLessThan(25);
});

test('a failed background snapshot is visible and can be retried safely', function () {
    config()->set('features.congressional_directory_ui', true);
    Queue::fake();
    $user = User::factory()->create();
    $profile = workbenchProfile('Failure Test');
    $draft = app(CongressionalOutreachWorkbenchService::class)->createDraft(
        workbenchList($user, [$profile]),
        $user->id,
        'Retry review'
    );
    $job = new BuildCongressionalOutreachDraftSnapshot($draft->id);
    $job->failed(new RuntimeException('Internal test detail'));
    $draft->refresh();

    expect($draft->status)->toBe('failed')
        ->and(data_get($draft->metadata, 'snapshot_error'))->toBe('The recipient snapshot could not be built. Please retry.')
        ->and(json_encode($draft->metadata))->not->toContain('Internal test detail');

    Livewire::actingAs($user)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertSee('The recipient snapshot could not be built')
        ->call('refreshSnapshot')
        ->assertSee('Building the recipient snapshot');

    Queue::assertPushed(
        BuildCongressionalOutreachDraftSnapshot::class,
        fn (BuildCongressionalOutreachDraftSnapshot $queuedJob) => $queuedJob->draftId === $draft->id
    );
    expect($draft->fresh()->status)->toBe('building');
});

test('email guesses follow source-aware house and senate conventions', function () {
    $guesses = app(CongressionalEmailGuessService::class);
    $house = workbenchProfile('Abbott Olivia H.');
    $house->currentPosition->office->update([
        'name' => 'HON. ANDREA SALINAS',
        'normalized_name' => 'hon. andrea salinas',
    ]);
    addWorkbenchObservation($house, 'ABBOTT OLIVIA H.', 'house_statement_of_disbursements_csv');

    $senate = workbenchProfile('Aalicyah D Moreno');
    $senate->update(['chamber' => 'Senate']);
    $senate->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATOR TOM COTTON',
        'normalized_name' => 'senator tom cotton',
    ]);
    addWorkbenchObservation($senate, 'MORENO, AALICYAH D', 'senate_secretary_report_pdf');

    $compound = workbenchProfile('Taylor Example');
    $compound->update(['chamber' => 'Senate']);
    $compound->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATOR CHRIS VAN HOLLEN',
        'normalized_name' => 'senator chris van hollen',
    ]);
    addWorkbenchObservation($compound, 'EXAMPLE, TAYLOR', 'senate_secretary_report_pdf');

    expect($guesses->guess($house)['email'])->toBe('olivia.abbott@mail.house.gov')
        ->and($guesses->guess($senate)['email'])->toBe('aalicyah_moreno@cotton.senate.gov')
        ->and($guesses->guess($compound)['email'])->toBe('taylor_example@vanhollen.senate.gov');
});

test('campaigns omit contact enrichment while legacy queued enrichment remains safe', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create();
    $house = workbenchProfile('Abbott Olivia H.');
    $house->currentPosition->office->update([
        'name' => 'HON. ANDREA SALINAS',
        'normalized_name' => 'hon. andrea salinas',
    ]);
    addWorkbenchObservation($house, 'ABBOTT OLIVIA H.', 'house_statement_of_disbursements_csv');
    $senate = workbenchProfile('Aalicyah D Moreno');
    $senate->update(['chamber' => 'Senate']);
    $senate->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATOR TOM COTTON',
        'normalized_name' => 'senator tom cotton',
    ]);
    addWorkbenchObservation($senate, 'MORENO, AALICYAH D', 'senate_secretary_report_pdf');
    $committee = workbenchProfile('Committee Staffer');
    $committee->currentPosition->office->update([
        'name' => 'DEMOCRATIC WOMENS CAUCUS',
        'normalized_name' => 'democratic womens caucus',
    ]);
    addWorkbenchObservation($committee, 'STAFFER COMMITTEE', 'house_statement_of_disbursements_csv');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($owner, [$house, $senate, $committee]),
        $owner,
        'Pattern pilot'
    );

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertDontSee('Generate provisional email guesses')
        ->assertDontSee('House pattern');

    (new GenerateCongressionalEmailGuesses(
        $draft->id,
        $owner->id,
        'Use the standard conventions for this pilot.',
        CongressionalEmailGuessService::HOUSE_PATTERN,
        CongressionalEmailGuessService::SENATE_PATTERN
    ))->handle(app(CongressionalEmailGuessService::class), $workbench);
    $draft->refresh();

    expect($house->emails()->sole()->email)->toBe('olivia.abbott@mail.house.gov')
        ->and($house->emails()->sole()->source_type)->toBe('guessed')
        ->and($house->emails()->sole()->verification_status)->toBe('unverified')
        ->and(data_get($house->emails()->sole()->metadata, 'guess.instructions'))->toBe('Use the standard conventions for this pilot.')
        ->and($senate->emails()->sole()->email)->toBe('aalicyah_moreno@cotton.senate.gov')
        ->and($committee->emails()->count())->toBe(0)
        ->and($draft->recipients()->where('profile_id', $house->id)->sole()->eligibility_tier)->toBe('limited')
        ->and($draft->recipients()->where('profile_id', $house->id)->sole()->review_status)->toBe('pending')
        ->and($draft->recipients()->where('profile_id', $committee->id)->sole()->exclusion_reason)->toBe('no_address')
        ->and(data_get($draft->metadata, 'email_guess_batch.status'))->toBe('completed')
        ->and(data_get($draft->metadata, 'email_guess_batch.generated'))->toBe(2)
        ->and(OutreachCampaign::query()->count())->toBe(0);
});

test('provisional email generation uses bounded database work as a list grows', function () {
    $user = User::factory()->create();
    $profiles = collect(range(1, 75))->map(function (int $index) {
        $profile = workbenchProfile(sprintf('GUESS%03d PERSON%03d', $index, $index));
        $profile->currentPosition->office->update([
            'name' => 'HON. TEST MEMBER '.$index,
            'normalized_name' => 'hon. test member '.$index,
        ]);
        addWorkbenchObservation($profile, sprintf('PERSON%03d GUESS%03d', $index, $index), 'house_statement_of_disbursements_csv');

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($user, $profiles), $user, 'Guess scale review');
    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $result = app(CongressionalEmailGuessService::class)->generateForDraft(
        $draft,
        $user->id,
        'Scale test',
    );

    expect($result['generated'])->toBe(75)
        ->and($result['skipped'])->toBe(0)
        ->and(DB::table('congressional_staff_emails')->count())->toBe(75)
        ->and(DB::table('congressional_staff_email_events')->where('event_type', 'address_added')->count())->toBe(75)
        ->and($queryCount)->toBeLessThan(20);
});

test('institution-specific formulas cover CBO senator intern accounts and verified Senate committees', function () {
    $cbo = workbenchProfile('Molly Saunders-Scott');
    $cbo->currentPosition->office->update([
        'name' => 'CONGRESSIONAL BUDGET OFFICE',
        'normalized_name' => 'congressional budget office',
        'office_code' => 'CBO',
        'office_type' => 'Institutional',
    ]);
    addWorkbenchObservation($cbo, 'SAUNDERS-SCOTT, MOLLY', 'cbo_staff_directory');

    $intern = workbenchProfile('Taylor Morgan');
    $intern->update(['chamber' => 'Senate']);
    $intern->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'INTERN COMPENSATION - BLUNT ROCHESTER',
        'normalized_name' => 'intern compensation - blunt rochester',
        'office_type' => 'Senator offices',
    ]);

    $committee = workbenchProfile('Jordan Rivera');
    $committee->update(['chamber' => 'Senate']);
    $committee->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'BUDGET S.RES. 94B (119TH) EXPENSES OF INQUIRIES AND INVESTIGATIONS',
        'normalized_name' => 'budget s res 94b 119th expenses of inquiries and investigations',
        'office_type' => 'Other Senate offices',
    ]);

    $unsupported = workbenchProfile('Casey Unknown');
    $unsupported->update(['chamber' => 'Senate']);
    $unsupported->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'SERGEANT AT ARMS AND DOORKEEPER OF THE SENATE',
        'normalized_name' => 'sergeant at arms and doorkeeper of the senate',
        'office_type' => 'Institutional',
    ]);
    $guesses = app(CongressionalEmailGuessService::class);

    expect($guesses->guess($cbo, allowAllHouseOffices: true))->toMatchArray([
        'email' => 'molly.saunders-scott@cbo.gov',
        'method' => 'cbo_first_dot_last',
        'pattern' => CongressionalEmailGuessService::CBO_PATTERN,
    ])->and($guesses->guess($intern, allowAllHouseOffices: true))->toMatchArray([
        'email' => 'taylor_morgan@bluntrochester.senate.gov',
        'method' => 'senate_first_underscore_last',
    ])->and($guesses->guess($committee, allowAllHouseOffices: true))->toMatchArray([
        'email' => 'jordan_rivera@budget.senate.gov',
        'method' => 'senate_committee_first_underscore_last',
        'pattern' => CongressionalEmailGuessService::SENATE_OFFICE_PATTERN,
    ])->and($guesses->guess($unsupported, allowAllHouseOffices: true))->toBeNull();
});

test('formula repairs correct only untouched generated guesses and pending workbench snapshots', function () {
    $user = User::factory()->create();
    $profile = workbenchProfile('Taylor Morgan');
    $profile->update(['chamber' => 'Senate']);
    $profile->currentPosition->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATOR LISA BLUNT ROCHESTER',
        'normalized_name' => 'senator lisa blunt rochester',
    ]);
    $email = $profile->emails()->create([
        'email' => 'taylor_morgan@rochester.senate.gov',
        'email_normalized' => 'taylor_morgan@rochester.senate.gov',
        'source_type' => 'guessed',
        'verification_status' => 'unverified',
        'metadata' => ['guess' => [
            'scope' => 'draft',
            'method' => 'senate_first_underscore_last',
            'pattern' => CongressionalEmailGuessService::SENATE_PATTERN,
        ]],
        'added_by' => $user->id,
    ]);
    $email->events()->create([
        'user_id' => $user->id,
        'event_key' => hash('sha256', 'address-added|'.$email->id),
        'event_type' => 'address_added',
        'evidence_strength' => 'low',
        'occurred_at' => now(),
    ]);
    $draft = builtWorkbenchDraft(
        app(CongressionalOutreachWorkbenchService::class),
        workbenchList($user, [$profile]),
        $user,
        'Formula repair test'
    );
    $guesses = app(CongressionalEmailGuessService::class);

    expect($guesses->estimateFormulaRepairs())->toBe(1)
        ->and($guesses->repairFormulaGuesses($user->id, 'Verified compound member domain'))->toBe(1)
        ->and($email->refresh()->email_normalized)->toBe('taylor_morgan@bluntrochester.senate.gov')
        ->and(data_get($email->metadata, 'guess.components.senator_last'))->toBe('bluntrochester')
        ->and($draft->recipients()->sole()->email_normalized)->toBe('taylor_morgan@bluntrochester.senate.gov')
        ->and($draft->recipients()->sole()->review_status)->toBe('pending')
        ->and($email->events()->where('event_type', 'address_corrected')->count())->toBe(1)
        ->and($guesses->estimateFormulaRepairs())->toBe(0);
});

test('database wide provisional generation preserves evidence and uses latest reported positions', function () {
    $user = User::factory()->create();
    $house = workbenchProfile('Jane Doe', false);
    $house->update(['status' => 'reported_historical']);
    $senate = workbenchProfile('Alex Smith', false);
    $senate->update(['chamber' => 'Senate', 'status' => 'reported_historical']);
    $senate->positions()->firstOrFail()->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATOR JOHN R. THUNE',
        'normalized_name' => 'senator john r thune',
    ]);
    $committee = workbenchProfile('Casey Clerk', false);
    $committee->update(['chamber' => 'Senate']);
    $committee->positions()->firstOrFail()->office->update([
        'chamber' => 'Senate',
        'name' => 'SENATE COMMITTEE ON DEMONSTRATIONS',
        'normalized_name' => 'senate committee on demonstrations',
    ]);
    $existing = workbenchProfile('Existing Evidence', false);
    app(CongressionalEmailEvidenceService::class)
        ->addAddress($existing, 'existing@mail.house.gov', 'observed');
    $guesses = app(CongressionalEmailGuessService::class);

    $estimate = $guesses->estimateAllProfiles();
    $result = $guesses->generateForAllProfiles($user->id, 'Global formula test');

    expect($estimate)->toMatchArray([
        'total' => 4,
        'already_addressed' => 1,
        'candidates' => 3,
        'guessable' => 2,
        'house' => 1,
        'senate' => 1,
        'unresolved' => 1,
        'reported_historical' => 2,
    ])->and($result)->toMatchArray([
        'candidates' => 3,
        'generated' => 2,
        'skipped' => 0,
        'unresolved' => 1,
        'house' => 1,
        'senate' => 1,
    ])->and($house->emails()->sole()->email)->toBe('jane.doe@mail.house.gov')
        ->and($senate->emails()->sole()->email)->toBe('alex_smith@thune.senate.gov')
        ->and($committee->emails()->count())->toBe(0)
        ->and($existing->emails()->sole()->email)->toBe('existing@mail.house.gov')
        ->and(data_get($house->emails()->sole()->metadata, 'guess.scope'))->toBe('global')
        ->and(CongressionalStaffEmailEvent::query()->where('event_type', 'address_added')->count())->toBe(3);
});

test('congressional outreach sends only approved recipients in batches of ten without duplicates', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $evidence = app(CongressionalEmailEvidenceService::class);
    $profiles = collect(range(1, 12))->map(function (int $index) use ($evidence) {
        $profile = workbenchProfile(sprintf('Batch Person %02d', $index));
        $evidence->addAddress($profile, "batch{$index}@mail.house.gov", 'sourced');

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, $profiles), $owner, 'Ten at a time');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello {{first_name}}', 'A resource for {{office}}.');
    $batches = app(CongressionalOutreachBatchService::class);

    expect(fn () => $batches->sendNextBatch($draft->fresh(), User::factory()->create()))
        ->toThrow(DomainException::class, 'Only the campaign owner');
    $first = $batches->sendNextBatch($draft->fresh(), $owner);

    expect($first['queued'])->toBe(10)
        ->and($first['campaign']->recipients()->count())->toBe(10)
        ->and($first['campaign']->recipients()->pluck('congressional_outreach_draft_recipient_id')->unique()->count())->toBe(10)
        ->and(data_get($first['campaign']->recipients()->first()->metadata, 'subject'))->toStartWith('Hello Batch');
    expect(fn () => $batches->sendNextBatch($draft->fresh(), $owner))
        ->toThrow(DomainException::class, 'current batch is still in progress');

    $first['campaign']->recipients()->update(['status' => 'sent', 'sent_at' => now()]);
    app(\App\Services\Outreach\OutreachCampaignService::class)->finalizeCampaignIfComplete($first['campaign']->id);
    $second = $batches->sendNextBatch($draft->fresh(), $owner);

    expect($second['queued'])->toBe(2)
        ->and(OutreachCampaignRecipient::query()->count())->toBe(12)
        ->and(OutreachCampaignRecipient::query()->pluck('congressional_outreach_draft_recipient_id')->unique()->count())->toBe(12)
        ->and($batches->summary($draft->fresh())['approved_unsent'])->toBe(0);
});

test('campaign batch size is configurable per campaign', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $evidence = app(CongressionalEmailEvidenceService::class);
    $profiles = collect(range(1, 7))->map(function (int $index) use ($evidence) {
        $profile = workbenchProfile(sprintf('Custom Batch Person %02d', $index));
        $evidence->addAddress($profile, "custom{$index}@mail.house.gov", 'sourced');

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, $profiles), $owner, 'Custom batch');
    $draft->update(['batch_size' => 3]);
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello {{first_name}}', 'A resource for {{office}}.');

    $first = app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner);

    expect($first['queued'])->toBe(3)
        ->and($first['campaign']->recipients()->count())->toBe(3)
        ->and(data_get($first['campaign']->metadata, 'batch_size_limit'))->toBe(3);
});

test('recurring congressional schedules queue configured batches and advance the cadence', function () {
    Queue::fake();
    $owner = User::factory()->create(['google_access_token' => 'schedule-test-token']);
    $evidence = app(CongressionalEmailEvidenceService::class);
    $profiles = collect(range(1, 3))->map(function (int $index) use ($evidence) {
        $profile = workbenchProfile(sprintf('Scheduled Person %02d', $index));
        $evidence->addAddress($profile, "scheduled{$index}@mail.house.gov", 'sourced');

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, $profiles), $owner, 'Scheduled batches');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello', 'A useful resource.');
    $draft->update([
        'batch_size' => 2,
        'delivery_mode' => 'recurring',
        'cadence_value' => 1,
        'cadence_unit' => 'hour',
        'schedule_status' => 'active',
        'next_send_at' => now()->subMinute(),
    ]);

    $result = app(CongressionalCampaignScheduleService::class)->runDue();

    expect($result['processed'])->toBe(1)
        ->and($draft->fresh()->schedule_status)->toBe('active')
        ->and($draft->fresh()->next_send_at)->toBeGreaterThan(now()->addMinutes(50))
        ->and(OutreachCampaign::query()->sole()->recipients()->count())->toBe(2);
});

test('campaign owners can pause active delivery from the campaign list and detail header', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create();
    $profile = workbenchProfile('Pause Campaign Person');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Pause campaign test');
    $draft->update([
        'delivery_mode' => 'recurring',
        'schedule_status' => 'active',
        'next_send_at' => now()->addHour(),
    ]);

    Livewire::actingAs($owner)
        ->test(CampaignIndex::class)
        ->assertSee('Pause campaign')
        ->call('pauseCampaign', $draft->id)
        ->assertHasNoErrors();

    expect($draft->fresh()->schedule_status)->toBe('paused')
        ->and($draft->fresh()->next_send_at)->toBeNull();

    $draft->update(['schedule_status' => 'active', 'next_send_at' => now()->addHour()]);

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft->fresh()])
        ->assertSee('Pause campaign')
        ->call('pauseSchedule')
        ->assertHasNoErrors();

    expect($draft->fresh()->schedule_status)->toBe('paused')
        ->and($draft->fresh()->next_send_at)->toBeNull();
});

test('recurring schedules auto approve safe provisional recipients and enforce the daily cap', function () {
    Queue::fake();
    Carbon::setTestNow('2026-07-16 13:00:00 UTC');
    $owner = User::factory()->create(['google_access_token' => 'automatic-rule-token']);
    $evidence = app(CongressionalEmailEvidenceService::class);
    $profiles = collect(range(1, 4))->map(function (int $index) use ($evidence, $owner) {
        $profile = workbenchProfile(sprintf('Automatic Person %02d', $index));
        $evidence->addAddress($profile, "automatic{$index}@mail.house.gov", 'guessed', $owner->id);

        return $profile;
    })->all();
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, $profiles), $owner, 'Automatic batches');
    $workbench->updateMessage($draft, 'Hello', 'A useful resource.');
    $draft->update([
        'batch_size' => 2,
        'delivery_mode' => 'recurring',
        'cadence_value' => 1,
        'cadence_unit' => 'hour',
        'timezone' => 'America/New_York',
        'auto_approve_provisional' => true,
        'daily_send_cap' => 3,
    ]);
    $schedules = app(CongressionalCampaignScheduleService::class);
    $schedules->activate($draft->fresh(), $owner, now()->subMinute());

    $firstResult = $schedules->runDue();
    $firstCampaign = OutreachCampaign::query()->sole();

    expect($firstResult['processed'])->toBe(1)
        ->and($firstCampaign->recipients()->count())->toBe(2)
        ->and($draft->recipients()->where('review_status', 'approved')->count())->toBe(2)
        ->and($schedules->dailyCapacityRemaining($draft->fresh()))->toBe(1);

    $firstCampaign->recipients()->update(['status' => 'sent', 'sent_at' => now()]);
    app(\App\Services\Outreach\OutreachCampaignService::class)->finalizeCampaignIfComplete($firstCampaign->id);
    $draft->fresh()->update(['next_send_at' => now()->subMinute()]);
    $secondResult = $schedules->runDue();
    $secondCampaign = OutreachCampaign::query()->latest('id')->firstOrFail();

    expect($secondResult['processed'])->toBe(1)
        ->and($secondCampaign->recipients()->count())->toBe(1)
        ->and($draft->recipients()->where('review_status', 'approved')->count())->toBe(3)
        ->and($schedules->dailyCapacityRemaining($draft->fresh()))->toBe(0);

    $secondCampaign->recipients()->update(['status' => 'sent', 'sent_at' => now()]);
    app(\App\Services\Outreach\OutreachCampaignService::class)->finalizeCampaignIfComplete($secondCampaign->id);
    expect($secondCampaign->fresh()->status)->toBe('sent')
        ->and($draft->fresh()->schedule_status)->toBe('active');
    $draft->fresh()->update(['next_send_at' => now()->subMinute()]);
    expect($draft->fresh()->schedule_status)->toBe('active')
        ->and($draft->fresh()->next_send_at)->toBeLessThan(now());
    $cappedResult = $schedules->runDue();

    expect($cappedResult['deferred'])->toBe(1)
        ->and(OutreachCampaign::query()->count())->toBe(2)
        ->and($draft->fresh()->next_send_at->setTimezone('America/New_York')->format('Y-m-d H:i'))->toBe('2026-07-17 09:00')
        ->and($draft->recipients()->where('review_status', 'pending')->count())->toBe(1);

    Carbon::setTestNow();
});

test('campaign builder saves audience message and configurable delivery rules', function () {
    Queue::fake();
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create(['timezone' => 'America/New_York']);
    $profile = workbenchProfile('Campaign Builder');
    $list = workbenchList($owner, [$profile]);

    Livewire::actingAs($owner)
        ->test(CampaignCreate::class)
        ->set('staffListId', $list->id)
        ->set('name', 'Technology directors')
        ->set('subject', 'A resource for {{office}}')
        ->set('bodyText', 'Hi {{first_name}}, please take a look.')
        ->set('batchSize', 37)
        ->set('deliveryMode', 'recurring')
        ->set('cadenceValue', 2)
        ->set('cadenceUnit', 'hour')
        ->call('createCampaign')
        ->assertHasNoErrors()
        ->assertRedirect();

    $campaign = CongressionalOutreachDraft::query()->where('user_id', $owner->id)->sole();

    expect($campaign->name)->toBe('Technology directors')
        ->and($campaign->batch_size)->toBe(37)
        ->and($campaign->delivery_mode)->toBe('recurring')
        ->and($campaign->cadence_value)->toBe(2)
        ->and($campaign->cadence_unit)->toBe('hour')
        ->and($campaign->schedule_status)->toBe('inactive');

    Queue::assertPushed(BuildCongressionalOutreachDraftSnapshot::class);
});

test('campaign owners can retry failed recipients without selecting a new batch', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $profile = workbenchProfile('Retry Recipient');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'retry@mail.house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Retry batch');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello', 'Resource');
    $batches = app(CongressionalOutreachBatchService::class);
    $batch = $batches->sendNextBatch($draft->fresh(), $owner);
    $recipient = $batch['campaign']->recipients()->sole();
    $recipient->update(['status' => 'failed', 'error_message' => 'Temporary Gmail error']);
    app(OutreachCampaignService::class)->finalizeCampaignIfComplete($batch['campaign']->id);

    $retry = $batches->retryFailedBatch($draft->fresh(), $owner);

    expect($retry['queued'])->toBe(1)
        ->and($batch['campaign']->fresh()->status)->toBe('sending')
        ->and($recipient->fresh()->status)->toBe('queued')
        ->and(OutreachCampaign::query()->count())->toBe(1)
        ->and(OutreachCampaignRecipient::query()->count())->toBe(1);
});

test('congressional delivery uses the personalized preview and records accepted-send evidence', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $profile = workbenchProfile('Taylor Recipient');
    $staffEmail = app(CongressionalEmailEvidenceService::class)
        ->addAddress($profile, 'taylor@mail.house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Personalized send');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello [Name]', 'Your role is [Title] at [Office]. Visit https://CongressH3.io.');
    $batch = app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner);
    $recipient = $batch['campaign']->recipients()->sole();
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('sendMessage')
        ->once()
        ->withArgs(fn ($user, $to, $subject, $body) => $user->is($owner)
            && $to === 'taylor@mail.house.gov'
            && $subject === 'Hello Taylor'
            && str_contains($body, 'Legislative Assistant')
            && str_contains($body, 'https://CongressH3.io'))
        ->andReturn(['message_id' => 'gmail-message-1']);

    (new SendOutreachCampaignRecipient($recipient->id))->handle(
        $gmail,
        app(OutreachCampaignService::class),
        app(OutreachSuppressionService::class),
        app(CongressionalEmailEvidenceService::class)
    );

    expect($recipient->fresh()->status)->toBe('sent')
        ->and($recipient->fresh()->external_message_id)->toBe('gmail-message-1')
        ->and($staffEmail->fresh()->last_sent_at)->not->toBeNull()
        ->and(CongressionalStaffEmailEvent::query()
            ->where('staff_email_id', $staffEmail->id)
            ->where('campaign_recipient_id', $recipient->id)
            ->where('event_type', 'send_accepted')
            ->exists())->toBeTrue();

    $reply = GmailMessage::query()->create([
        'user_id' => $owner->id,
        'gmail_message_id' => 'gmail-reply-1',
        'gmail_thread_id' => 'gmail-thread-1',
        'subject' => 'Re: Hello Taylor',
        'snippet' => 'Thanks, this is useful.',
        'from_email' => 'taylor@mail.house.gov',
        'from_name' => 'Taylor Recipient',
        'to_emails' => [$owner->email],
        'sent_at' => now()->addMinute(),
        'is_inbound' => true,
        'labels' => ['INBOX'],
    ]);

    expect(app(CongressionalStaffChangeDetector::class)->detect($reply))->toBeNull()
        ->and($staffEmail->fresh()->verification_status)->toBe('replied')
        ->and(CongressionalStaffEmailEvent::query()
            ->where('campaign_recipient_id', $recipient->id)
            ->where('event_type', 'human_reply')
            ->exists())->toBeTrue();

    $failure = GmailMessage::query()->create([
        'user_id' => $owner->id,
        'gmail_message_id' => 'gmail-bounce-1',
        'gmail_thread_id' => 'gmail-bounce-thread-1',
        'subject' => 'Delivery Status Notification (Failure)',
        'snippet' => 'Address not found. Your message was not delivered to taylor@mail.house.gov.',
        'from_email' => 'mailer-daemon@googlemail.com',
        'from_name' => 'Mail Delivery Subsystem',
        'to_emails' => [$owner->email],
        'sent_at' => now()->subHours(12),
        'is_inbound' => true,
        'labels' => ['INBOX'],
    ]);

    $failureSignal = app(CongressionalStaffChangeDetector::class)->detect($failure);
    expect($failureSignal?->signal_type)->toBe('delivery_failure');

    CongressionalStaffChangeSignal::query()->whereKey($failureSignal->id)->update([
        'created_at' => now()->subDay(),
    ]);

    $analytics = app(CongressionalOutreachBatchService::class)->analytics($draft->fresh());
    expect($analytics['statuses']['sent'])->toBe(1)
        ->and($analytics['events']['send_accepted'])->toBe(1)
        ->and($analytics['events']['human_reply'])->toBe(1)
        ->and($analytics['bounce_signals'])->toBe(1)
        ->and($analytics['clicks_tracked'])->toBeTrue();
});

test('batch delivery refuses unresolved personalization placeholders', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $profile = workbenchProfile('Unresolved Recipient');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'unresolved@mail.house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Unresolved message');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Hello [Unknown Field]', 'Hi [Name]');

    expect(fn () => app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner))
        ->toThrow(DomainException::class, '[Unknown Field]')
        ->and(OutreachCampaign::query()->count())->toBe(0);
});

test('recipient review supports selecting and approving multiple provisional addresses', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create();
    $profiles = [workbenchProfile('Bulk One'), workbenchProfile('Bulk Two')];
    $evidence = app(CongressionalEmailEvidenceService::class);
    $evidence->addAddress($profiles[0], 'one.bulk@mail.house.gov', 'guessed');
    $evidence->addAddress($profiles[1], 'two.bulk@mail.house.gov', 'guessed');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, $profiles), $owner, 'Bulk review');
    $recipientIds = $draft->recipients()->pluck('id')->all();

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->set('selectedRecipientIds', $recipientIds)
        ->call('approveSelectedRecipients')
        ->assertHasNoErrors()
        ->assertSet('selectedRecipientIds', []);

    expect($draft->recipients()->where('review_status', 'approved')->count())->toBe(2);
});

test('tracking records unique recipient engagement and analytics exposes every recipient', function () {
    config()->set('features.congressional_directory_ui', true);
    Queue::fake();
    $owner = User::factory()->create();
    $profile = workbenchProfile('Tracked Recipient');
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'tracked@mail.house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Tracked analytics');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'A useful resource', 'Visit https://example.org/resource');
    $batch = app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner);
    $recipient = $batch['campaign']->recipients()->sole();
    $html = app(OutreachTrackingService::class)->trackedHtml($recipient, 'Visit https://example.org/resource');
    $recipient->update(['status' => 'sent', 'sent_at' => now()]);

    expect($html)->toContain(route('outreach.track.open', ['token' => $recipient->tracking_token]))
        ->and($html)->toContain(route('outreach.track.click', ['token' => $recipient->tracking_token]));

    $this->get(route('outreach.track.open', ['token' => $recipient->tracking_token]))->assertOk();
    $this->get(route('outreach.track.open', ['token' => $recipient->tracking_token]))->assertOk();
    $this->get(route('outreach.track.click', ['token' => $recipient->tracking_token]).'?url='.rawurlencode('https://example.org/resource'))
        ->assertRedirect('https://example.org/resource');

    $recipient->refresh();
    expect($recipient->open_count)->toBe(2)
        ->and($recipient->click_count)->toBe(1)
        ->and($recipient->opened_at)->not->toBeNull()
        ->and($recipient->clicked_at)->not->toBeNull()
        ->and($recipient->events()->where('event_type', 'open')->count())->toBe(2)
        ->and($recipient->events()->where('event_type', 'click')->count())->toBe(1);

    Livewire::actingAs($owner)
        ->test(OutreachAnalytics::class, ['draft' => $draft])
        ->assertSee('Tracked Recipient')
        ->assertSee('100% open rate')
        ->assertSee('100% click rate');
});

test('campaign and Gmail activity is logged without duplicating the outbound send', function () {
    $owner = User::factory()->create();
    $person = Person::query()->create(['name' => 'Timeline Staff', 'email' => 'timeline@mail.house.gov']);
    $profile = workbenchProfile('Timeline Staff');
    $profile->update(['person_id' => $person->id]);
    app(CongressionalEmailEvidenceService::class)->addAddress($profile, 'timeline@mail.house.gov', 'sourced');
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft($workbench, workbenchList($owner, [$profile]), $owner, 'Timeline campaign');
    $workbench->approveAllEligible($draft, $owner->id);
    $workbench->updateMessage($draft, 'Timeline subject', 'Hello');
    Queue::fake();
    $batch = app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner);
    $recipient = $batch['campaign']->recipients()->sole();
    $recipient->update(['status' => 'sent', 'external_message_id' => 'gmail-outbound', 'sent_at' => now()]);
    $activities = app(ContactActivityService::class);
    $activities->recordCampaignSend($recipient->fresh());

    $outbound = GmailMessage::query()->create([
        'user_id' => $owner->id,
        'gmail_message_id' => 'gmail-outbound',
        'gmail_thread_id' => 'timeline-thread',
        'subject' => 'Timeline subject',
        'snippet' => 'Hello',
        'from_email' => $owner->email,
        'to_emails' => ['timeline@mail.house.gov'],
        'sent_at' => now(),
        'is_inbound' => false,
    ]);
    $outbound->setRelation('user', $owner);
    $activities->recordGmailMessage($outbound);

    expect(ContactActivity::query()->where('campaign_recipient_id', $recipient->id)->count())->toBe(2)
        ->and(ContactActivity::query()->where('campaign_recipient_id', $recipient->id)->where('gmail_message_id', $outbound->id)->count())->toBe(2);

    $inbound = GmailMessage::query()->create([
        'user_id' => $owner->id,
        'person_id' => $person->id,
        'gmail_message_id' => 'gmail-inbound',
        'gmail_thread_id' => 'timeline-thread',
        'subject' => 'Re: Timeline subject',
        'snippet' => 'Thanks for reaching out.',
        'from_email' => 'timeline@mail.house.gov',
        'to_emails' => [$owner->email],
        'sent_at' => now()->addMinute(),
        'is_inbound' => true,
    ]);
    $activities->recordGmailMessage($inbound);

    expect($recipient->fresh()->replied_at)->not->toBeNull()
        ->and($person->contactActivities()->where('direction', 'inbound')->exists())->toBeTrue()
        ->and($profile->contactActivities()->where('direction', 'inbound')->exists())->toBeTrue();
});

test('approved recipient preview uses guess evidence for names links and carousel navigation', function () {
    config()->set('features.congressional_directory_ui', true);
    $owner = User::factory()->create();
    $firstProfile = workbenchProfile('Aaronson Braiden');
    $secondProfile = workbenchProfile('Baker Casey');
    $evidence = app(CongressionalEmailEvidenceService::class);
    $firstEmail = $evidence->addAddress($firstProfile, 'braiden.aaronson@mail.house.gov', 'guessed');
    $firstEmail->update(['metadata' => ['guess' => ['components' => ['first' => 'braiden', 'last' => 'aaronson']]]]);
    $secondEmail = $evidence->addAddress($secondProfile, 'casey.baker@mail.house.gov', 'guessed');
    $secondEmail->update(['metadata' => ['guess' => ['components' => ['first' => 'casey', 'last' => 'baker']]]]);
    $workbench = app(CongressionalOutreachWorkbenchService::class);
    $draft = builtWorkbenchDraft(
        $workbench,
        workbenchList($owner, [$firstProfile, $secondProfile]),
        $owner,
        'Preview carousel'
    );
    $firstRecipient = $draft->recipients()->where('profile_id', $firstProfile->id)->sole();
    $secondRecipient = $draft->recipients()->where('profile_id', $secondProfile->id)->sole();
    $workbench->approve($firstRecipient, $owner->id);
    $workbench->approve($secondRecipient, $owner->id);
    $workbench->updateMessage($draft, 'Hello [Name]', 'Hi [Name], visit CongressH3.io.');

    $preview = $workbench->preview($draft->fresh(), $firstRecipient->fresh());
    expect($preview['subject'])->toBe('Hello Braiden')
        ->and($preview['body'])->toBe('Hi Braiden, visit CongressH3.io.')
        ->and($preview['personalization']['first_name'])->toBe('Braiden')
        ->and($preview['personalization']['full_name'])->toBe('Braiden Aaronson')
        ->and($preview['body_html'])->toContain('<mark')
        ->and($preview['body_html'])->toContain('>Braiden</mark>')
        ->and($preview['body_html'])->toContain('href="https://CongressH3.io"')
        ->and($preview['links'])->toBe([['display' => 'CongressH3.io', 'url' => 'https://CongressH3.io']]);

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft->fresh()])
        ->assertSee('Approved recipient 1 of 2')
        ->assertSee('Braiden')
        ->assertSee('CongressH3.io')
        ->set('subject', 'Updated preview for [Name]')
        ->set('bodyText', 'Review Example.org with [Name].')
        ->call('refreshPreview')
        ->assertSee('Previewing current editor text. These changes are not saved yet.')
        ->assertSee('Updated preview for')
        ->assertSee('Example.org')
        ->call('showPreview', $secondRecipient->id)
        ->assertSee('Approved recipient 2 of 2')
        ->assertSee('Casey');

    expect($draft->fresh()->subject)->toBe('Hello [Name]')
        ->and($draft->fresh()->body_text)->toBe('Hi [Name], visit CongressH3.io.');
});

test('gmail messages include plain text and clickable html alternatives', function () {
    $service = app(GoogleGmailService::class);
    $method = new ReflectionMethod($service, 'buildRawMessage');
    $encoded = $method->invoke($service, 'recipient@example.com', 'Resource', 'Visit CongressH3.io today.');
    $mime = base64_decode(strtr($encoded, '-_', '+/').str_repeat('=', (4 - strlen($encoded) % 4) % 4));

    expect($mime)->toContain('Content-Type: multipart/alternative')
        ->and($mime)->toContain('Content-Type: text/plain; charset=UTF-8')
        ->and($mime)->toContain('Content-Type: text/html; charset=UTF-8')
        ->and($mime)->toContain('<a href="https://CongressH3.io"')
        ->and($mime)->toContain('Visit CongressH3.io today.');
});
