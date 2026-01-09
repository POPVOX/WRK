<?php

use App\Http\Controllers\GoogleAuthController;
use App\Livewire\Dashboard;
use App\Livewire\Meetings\MeetingCapture;
use App\Livewire\Meetings\MeetingDetail;
use App\Livewire\Meetings\MeetingList;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Onboarding
    Route::get('/onboarding', \App\Livewire\Onboarding::class)->name('onboarding');

    // Meetings
    Route::get('/meetings', MeetingList::class)->name('meetings.index');
    Route::get('/meetings/new', MeetingCapture::class)->name('meetings.create');
    Route::get('/meetings/{meeting}/edit', MeetingCapture::class)->name('meetings.edit');
    Route::get('/meetings/{meeting}', MeetingDetail::class)->name('meetings.show');

    // Projects
    Route::get('/projects', \App\Livewire\Projects\ProjectList::class)->name('projects.index');
    Route::get('/projects/create', \App\Livewire\Projects\ProjectCreate::class)->name('projects.create');
    Route::get('/projects/{project}/duplicate', \App\Livewire\Projects\ProjectCreate::class)->name('projects.duplicate');
    Route::get('/projects/{project}', \App\Livewire\Projects\ProjectShow::class)->name('projects.show');
    // Redirect old workspace URL to project page with chat tab
    Route::get('/projects/{project}/workspace', function (\App\Models\Project $project) {
        return redirect()->route('projects.show', ['project' => $project, 'activeTab' => 'chat']);
    })->name('projects.workspace');

    // Organizations
    Route::get('/organizations', \App\Livewire\Organizations\OrganizationIndex::class)->name('organizations.index');
    Route::get('/organizations/{organization}', \App\Livewire\Organizations\OrganizationShow::class)->name('organizations.show');

    // People (legacy)
    Route::get('/people', \App\Livewire\People\PersonIndex::class)->name('people.index');
    Route::get('/people/{person}', \App\Livewire\People\PersonShow::class)->name('people.show');

    // Contacts (CRM-friendly alias)
    Route::get('/contacts', \App\Livewire\People\PersonIndex::class)->name('contacts.index');
    Route::get('/contacts/{person}', \App\Livewire\People\PersonShow::class)->name('contacts.show');

    // Google Calendar OAuth
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::post('/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('google.disconnect');

    // Team Hub
    Route::get('/team', \App\Livewire\Team\TeamHub::class)->name('team.hub');
    Route::get('/team/{member}', \App\Livewire\Team\TeamMemberProfile::class)->name('team.member.profile');

    // Knowledge Hub
    Route::get('/knowledge', \App\Livewire\KnowledgeHub::class)->name('knowledge.hub');

    // Knowledge Base (Org-wide)
    Route::get('/knowledge-base', \App\Livewire\KnowledgeBase::class)->name('knowledge.base');

    // Media & Press
    Route::get('/media', \App\Livewire\Media\MediaIndex::class)->name('media.index');

    // Accomplishments & Wins
    Route::get('/accomplishments', \App\Livewire\Accomplishments\AccomplishmentsIndex::class)->name('accomplishments.index');
    Route::get('/accomplishments/team', \App\Livewire\Accomplishments\ManagementDashboard::class)->name('accomplishments.team');
    Route::get('/accomplishments/user/{userId}', \App\Livewire\Accomplishments\AccomplishmentsIndex::class)->name('accomplishments.user');

    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/staff', \App\Livewire\Admin\StaffManagement::class)->name('admin.staff');
        Route::get('/metrics', \App\Livewire\Admin\Metrics::class)->name('admin.metrics');
        Route::get('/permissions', \App\Livewire\Admin\Permissions::class)->name('admin.permissions');
        Route::get('/feedback', \App\Livewire\Admin\FeedbackManagement::class)->name('admin.feedback');
    });

    // Funders & Grants (Admin only - access check in component)
    Route::get('/funders', \App\Livewire\Grants\GrantIndex::class)->name('grants.index');
    Route::get('/funders/{grant}', \App\Livewire\Grants\GrantShow::class)->name('grants.show');

    // API routes
    Route::get('/api/mentions/search', [\App\Http\Controllers\Api\MentionSearchController::class, 'search'])->name('api.mentions.search');
    Route::get('/api/organizations/search', [\App\Http\Controllers\Api\MentionSearchController::class, 'searchOrganizations'])->name('api.organizations.search');
    Route::get('/api/people/search', [\App\Http\Controllers\Api\MentionSearchController::class, 'searchPeople'])->name('api.people.search');
    Route::get('/api/issues/search', [\App\Http\Controllers\Api\MentionSearchController::class, 'searchIssues'])->name('api.issues.search');
    Route::get('/api/staff/search', [\App\Http\Controllers\Api\MentionSearchController::class, 'searchStaff'])->name('api.staff.search');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
