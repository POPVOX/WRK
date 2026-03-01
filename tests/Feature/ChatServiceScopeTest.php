<?php

use App\Models\Action;
use App\Models\Issue;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\User;
use App\Services\ChatService;

class ExposedChatService extends ChatService
{
    public function contextFor(string $query, ?User $user): array
    {
        return $this->retrieveContext($query, $user);
    }
}

function seedScopedChatFixture(): array
{
    $alice = User::factory()->create([
        'name' => 'Alice Example',
        'access_level' => 'staff',
    ]);

    $bob = User::factory()->create([
        'name' => 'Bob Example',
        'access_level' => 'staff',
    ]);

    $alphaOrg = Organization::query()->create([
        'name' => 'Alpha Alliance',
        'type' => 'Nonprofit',
        'notes' => 'Alpha notes',
    ]);
    $betaOrg = Organization::query()->create([
        'name' => 'Beta Alliance',
        'type' => 'Nonprofit',
        'notes' => 'Beta notes',
    ]);

    $alphaPerson = Person::query()->create([
        'name' => 'Alice Alliance Contact',
        'organization_id' => $alphaOrg->id,
        'title' => 'Director',
    ]);
    $betaPerson = Person::query()->create([
        'name' => 'Bob Alliance Contact',
        'organization_id' => $betaOrg->id,
        'title' => 'Director',
    ]);

    $alphaIssue = Issue::query()->create(['name' => 'Alpha Alliance Issue']);
    $betaIssue = Issue::query()->create(['name' => 'Beta Alliance Issue']);

    $alphaProject = Project::query()->create([
        'name' => 'Alpha Alliance Project',
        'description' => 'Alpha delivery plan',
        'status' => 'active',
        'created_by' => $alice->id,
        'lead' => 'Alice',
    ]);
    $betaProject = Project::query()->create([
        'name' => 'Beta Alliance Project',
        'description' => 'Beta delivery plan',
        'status' => 'active',
        'created_by' => $bob->id,
        'lead' => 'Bob',
    ]);

    $alphaMeeting = Meeting::query()->create([
        'user_id' => $alice->id,
        'meeting_date' => now()->toDateString(),
        'ai_summary' => 'Discussed Alpha Alliance next steps',
        'status' => 'pending',
    ]);
    $betaMeeting = Meeting::query()->create([
        'user_id' => $bob->id,
        'meeting_date' => now()->toDateString(),
        'ai_summary' => 'Discussed Beta Alliance next steps',
        'status' => 'pending',
    ]);

    $alphaMeeting->organizations()->attach($alphaOrg->id);
    $betaMeeting->organizations()->attach($betaOrg->id);
    $alphaMeeting->people()->attach($alphaPerson->id);
    $betaMeeting->people()->attach($betaPerson->id);
    $alphaMeeting->issues()->attach($alphaIssue->id);
    $betaMeeting->issues()->attach($betaIssue->id);
    $alphaMeeting->projects()->attach($alphaProject->id);
    $betaMeeting->projects()->attach($betaProject->id);

    Action::query()->create([
        'meeting_id' => $alphaMeeting->id,
        'description' => 'Follow up with Alpha Alliance',
        'status' => 'pending',
        'priority' => 'high',
        'assigned_to' => $alice->id,
    ]);
    Action::query()->create([
        'meeting_id' => $betaMeeting->id,
        'description' => 'Follow up with Beta Alliance',
        'status' => 'pending',
        'priority' => 'high',
        'assigned_to' => $bob->id,
    ]);

    return [
        'alice' => $alice,
        'bob' => $bob,
    ];
}

test('chat context is scoped to the requesting staff user', function () {
    $fixture = seedScopedChatFixture();
    $service = new ExposedChatService;

    $context = $service->contextFor('alliance recent pending', $fixture['alice']);
    $issueContext = $service->contextFor('alliance issue', $fixture['alice']);

    $organizationNames = collect($context['organizations'] ?? [])->pluck('name')->all();
    $peopleNames = collect($context['people'] ?? [])->pluck('name')->all();
    $issueNames = collect($issueContext['issues'] ?? [])->pluck('name')->all();
    $projectNames = collect($context['projects'] ?? [])->pluck('name')->all();
    $actionDescriptions = collect($context['pending_actions'] ?? [])->pluck('description')->all();
    $recentMeetingOrganizations = collect($context['recent_meetings'] ?? [])->pluck('organizations')->implode(' ');

    expect($organizationNames)->toContain('Alpha Alliance');
    expect($organizationNames)->not->toContain('Beta Alliance');
    expect($peopleNames)->toContain('Alice Alliance Contact');
    expect($peopleNames)->not->toContain('Bob Alliance Contact');
    expect($issueNames)->toContain('Alpha Alliance Issue');
    expect($issueNames)->not->toContain('Beta Alliance Issue');
    expect($projectNames)->toContain('Alpha Alliance Project');
    expect($projectNames)->not->toContain('Beta Alliance Project');
    expect($actionDescriptions)->toContain('Follow up with Alpha Alliance');
    expect($actionDescriptions)->not->toContain('Follow up with Beta Alliance');
    expect($recentMeetingOrganizations)->toContain('Alpha Alliance');
    expect($recentMeetingOrganizations)->not->toContain('Beta Alliance');

    expect($context['system_stats']['total_meetings'] ?? null)->toBe(1);
    expect($context['system_stats']['total_projects'] ?? null)->toBe(1);
    expect($context['system_stats']['total_organizations'] ?? null)->toBe(1);
    expect($context['system_stats']['total_people'] ?? null)->toBe(1);
    expect($context['system_stats']['pending_actions'] ?? null)->toBe(1);
});

test('chat context remains global for admin users', function () {
    seedScopedChatFixture();
    $service = new ExposedChatService;

    $admin = User::factory()->admin()->create([
        'name' => 'Admin User',
    ]);

    $context = $service->contextFor('alliance recent pending', $admin);

    $organizationNames = collect($context['organizations'] ?? [])->pluck('name')->all();
    $actionDescriptions = collect($context['pending_actions'] ?? [])->pluck('description')->all();
    $recentMeetingOrganizations = collect($context['recent_meetings'] ?? [])->pluck('organizations')->implode(' ');

    expect($organizationNames)->toContain('Alpha Alliance');
    expect($organizationNames)->toContain('Beta Alliance');
    expect($actionDescriptions)->toContain('Follow up with Alpha Alliance');
    expect($actionDescriptions)->toContain('Follow up with Beta Alliance');
    expect($recentMeetingOrganizations)->toContain('Alpha Alliance');
    expect($recentMeetingOrganizations)->toContain('Beta Alliance');
    expect($context['system_stats']['total_meetings'] ?? null)->toBe(2);
    expect($context['system_stats']['total_projects'] ?? null)->toBe(2);
    expect($context['system_stats']['total_organizations'] ?? null)->toBe(2);
    expect($context['system_stats']['total_people'] ?? null)->toBe(2);
    expect($context['system_stats']['pending_actions'] ?? null)->toBe(2);
});
