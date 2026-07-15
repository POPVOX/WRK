<?php

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Livewire\CongressionalDirectory\OutreachDraftShow;
use App\Livewire\CongressionalDirectory\StaffListsIndex;
use App\Models\CongressionalOffice;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachCampaign;
use App\Models\OutreachEmailSuppression;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
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
        ->assertSee('Dry run only')
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
