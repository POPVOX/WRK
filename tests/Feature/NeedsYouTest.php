<?php

use App\Livewire\NeedsYou;
use App\Models\Action;
use App\Models\Agent;
use App\Models\AgentRun;
use App\Models\AgentSuggestion;
use App\Models\Grant;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ReportingRequirement;
use App\Models\User;
use App\Services\Attention\AttentionItemService;
use Livewire\Livewire;

test('attention service combines current work and respects user and grant visibility', function () {
    $this->travelTo(now()->startOfDay()->setDate(2026, 7, 10)->setTime(9, 0));

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['created_by' => $user->id]);

    $action = Action::create([
        'title' => 'Send meeting follow-up',
        'description' => 'Send meeting follow-up',
        'assigned_to' => $user->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today(),
        'project_id' => $project->id,
    ]);
    $otherAction = Action::create([
        'title' => 'Someone else task',
        'description' => 'Someone else task',
        'assigned_to' => $otherUser->id,
        'status' => Action::STATUS_PENDING,
        'due_date' => today(),
    ]);

    $projectTask = ProjectTask::create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
        'title' => 'Review the project brief',
        'status' => 'in_progress',
        'priority' => 'medium',
        'due_date' => today()->addDays(2),
    ]);

    $upcomingMeeting = Meeting::create([
        'user_id' => $user->id,
        'title' => 'Partner planning call',
        'meeting_date' => today()->addDay(),
        'status' => Meeting::STATUS_NEW,
    ]);
    $pastMeeting = Meeting::create([
        'user_id' => $user->id,
        'title' => 'Funder debrief',
        'meeting_date' => today()->subDay(),
        'status' => Meeting::STATUS_PENDING,
    ]);

    $organization = Organization::create(['name' => 'Visible Funder', 'type' => 'funder', 'status' => 'active']);
    $visibleGrant = Grant::create([
        'organization_id' => $organization->id,
        'name' => 'Visible Grant',
        'status' => 'active',
        'visibility' => 'all',
    ]);
    $visibleRequirement = ReportingRequirement::create([
        'grant_id' => $visibleGrant->id,
        'name' => 'Quarterly report',
        'type' => 'progress_report',
        'status' => 'pending',
        'due_date' => today()->addDays(10),
    ]);

    $restrictedGrant = Grant::create([
        'organization_id' => $organization->id,
        'name' => 'Management Grant',
        'status' => 'active',
        'visibility' => 'management',
    ]);
    ReportingRequirement::create([
        'grant_id' => $restrictedGrant->id,
        'name' => 'Restricted report',
        'type' => 'progress_report',
        'status' => 'pending',
        'due_date' => today()->addDays(5),
    ]);

    $agent = Agent::create([
        'name' => 'Project Agent',
        'scope' => Agent::SCOPE_PROJECT,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'owner_user_id' => $user->id,
    ]);
    $run = AgentRun::create([
        'agent_id' => $agent->id,
        'requested_by' => $user->id,
        'status' => 'completed',
    ]);
    $suggestion = AgentSuggestion::create([
        'agent_id' => $agent->id,
        'run_id' => $run->id,
        'suggestion_type' => 'draft_follow_up',
        'title' => 'Review agent follow-up draft',
        'risk_level' => 'medium',
        'approval_status' => AgentSuggestion::STATUS_PENDING,
    ]);

    $items = app(AttentionItemService::class)->forUser($user);
    $ids = $items->pluck('id');

    expect($ids)
        ->toContain('action-'.$action->id)
        ->toContain('project-task-'.$projectTask->id)
        ->toContain('meeting-prep-'.$upcomingMeeting->id)
        ->toContain('meeting-notes-'.$pastMeeting->id)
        ->toContain('reporting-requirement-'.$visibleRequirement->id)
        ->toContain('agent-suggestion-'.$suggestion->id)
        ->not->toContain('action-'.$otherAction->id);

    expect($items->pluck('title'))->not->toContain('Restricted report');
    expect($items->first()['bucket'])->toBe('now');
});

test('needs you page requires authentication and renders the attention queue', function () {
    $this->get(route('needs-you.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    Action::create([
        'title' => 'Prepare board materials',
        'description' => 'Prepare board materials',
        'assigned_to' => $user->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today(),
    ]);

    $this->actingAs($user)
        ->get(route('needs-you.index'))
        ->assertOk()
        ->assertSee('Needs You')
        ->assertSee('Prepare board materials')
        ->assertSee('Read-only pilot');
});

test('needs you filters attention items without changing records', function () {
    $user = User::factory()->create();
    Action::create([
        'title' => 'Filtered task',
        'description' => 'Filtered task',
        'assigned_to' => $user->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today(),
    ]);
    Meeting::create([
        'user_id' => $user->id,
        'title' => 'Filtered meeting',
        'meeting_date' => today()->addDay(),
        'status' => Meeting::STATUS_NEW,
    ]);

    Livewire::actingAs($user)
        ->test(NeedsYou::class)
        ->call('setFilter', 'tasks')
        ->assertSet('filter', 'tasks')
        ->assertSee('Filtered task')
        ->assertDontSee('Prepare for Filtered meeting');
});
