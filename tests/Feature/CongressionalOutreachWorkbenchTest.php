<?php

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Jobs\GenerateCongressionalEmailGuesses;
use App\Jobs\SendOutreachCampaignRecipient;
use App\Livewire\CongressionalDirectory\OutreachDraftShow;
use App\Livewire\CongressionalDirectory\StaffListsIndex;
use App\Models\CongressionalOffice;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffObservation;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachCampaign;
use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachEmailSuppression;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use App\Services\CongressionalDirectory\CongressionalOutreachBatchService;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use App\Services\GoogleGmailService;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\OutreachSuppressionService;
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
    workbenchList($user, [$profile]);

    Livewire::actingAs($user)
        ->test(StaffListsIndex::class)
        ->set('draftName', 'Resource pilot')
        ->call('createDryRun');

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
        ->test(StaffListsIndex::class)
        ->assertSee('Shared with me')
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

test('owners can queue a traceable provisional guess batch without sending', function () {
    config()->set('features.congressional_directory_ui', true);
    Queue::fake();
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
        ->set('batchSenatePattern', '{first}_{last}@{unknown}.senate.gov')
        ->call('generateEmailGuesses')
        ->assertHasErrors('batchSenatePattern');
    Queue::assertNothingPushed();

    Livewire::actingAs($owner)
        ->test(OutreachDraftShow::class, ['draft' => $draft])
        ->assertSeeText('2 guessable')
        ->set('batchInstructions', 'Use the standard conventions for this pilot.')
        ->call('generateEmailGuesses')
        ->assertHasNoErrors()
        ->assertSee('Generating provisional email guesses');

    Queue::assertPushed(
        GenerateCongressionalEmailGuesses::class,
        fn (GenerateCongressionalEmailGuesses $job) => $job->draftId === $draft->id
            && $job->instructions === 'Use the standard conventions for this pilot.'
    );

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
    $workbench->updateMessage($draft, 'Hello {{first_name}}', 'Your role is {{title}} at {{office}}.');
    $batch = app(CongressionalOutreachBatchService::class)->sendNextBatch($draft->fresh(), $owner);
    $recipient = $batch['campaign']->recipients()->sole();
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('sendMessage')
        ->once()
        ->withArgs(fn ($user, $to, $subject, $body) => $user->is($owner)
            && $to === 'taylor@mail.house.gov'
            && $subject === 'Hello Taylor'
            && str_contains($body, 'Legislative Assistant'))
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
});
