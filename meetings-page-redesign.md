# Meetings Page Redesign Implementation

## Overview

Redesign the Meetings index page from a flat list to an action-oriented, contextual view with three sections: Upcoming, Needs Notes, and Completed.

## Current State

- Flat list of past meetings
- "New" badges with unclear meaning
- No upcoming meetings shown
- No visual hierarchy or context
- Missing action prompts

## Target State

- Three-section layout (Upcoming → Needs Notes → Completed)
- Rich context on each meeting (attendees, org, issues, commitments)
- Clear calls-to-action ([Prep], [Add Notes])
- Note snippets for completed meetings
- Calendar sync status

---

## Database Considerations

### Meeting Model Should Have

```php
// Scopes needed
public function scopeUpcoming($query)
{
    return $query->where('scheduled_at', '>', now())
                 ->orderBy('scheduled_at', 'asc');
}

public function scopePast($query)
{
    return $query->where('scheduled_at', '<=', now())
                 ->orderBy('scheduled_at', 'desc');
}

public function scopeNeedsNotes($query)
{
    return $query->past()
                 ->where(function ($q) {
                     $q->whereNull('notes')
                       ->orWhere('notes', '');
                 });
}

public function scopeWithNotes($query)
{
    return $query->past()
                 ->whereNotNull('notes')
                 ->where('notes', '!=', '');
}

// Helper methods
public function hasNotes(): bool
{
    return !empty($this->notes);
}

public function isUpcoming(): bool
{
    return $this->scheduled_at > now();
}

public function isPast(): bool
{
    return $this->scheduled_at <= now();
}

public function getNotesPreviewAttribute(): ?string
{
    if (!$this->notes) return null;
    return Str::limit(strip_tags($this->notes), 100);
}

// Get open commitments for this meeting's organizations/people
public function getOpenCommitmentsCountAttribute(): int
{
    // Commitments related to attendees or their organizations
    return Commitment::where('status', 'open')
        ->where(function ($q) {
            $q->whereIn('person_id', $this->attendees->pluck('id'))
              ->orWhereIn('organization_id', $this->organizations->pluck('id'));
        })
        ->count();
}
```

### Ensure These Relationships Exist

```php
// Meeting.php
public function attendees(): BelongsToMany
{
    return $this->belongsToMany(Person::class, 'meeting_attendees');
}

public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class, 'meeting_organizations');
}

public function issues(): BelongsToMany
{
    return $this->belongsToMany(Issue::class, 'meeting_issues');
}

public function projects(): BelongsToMany
{
    return $this->belongsToMany(Project::class, 'meeting_projects');
}

public function commitments(): HasMany
{
    return $this->hasMany(Commitment::class);
}
```

---

## Livewire Component

### app/Livewire/Meetings/Index.php

```php
<?php

namespace App\Livewire\Meetings;

use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Issue;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $status = ''; // upcoming, needs_notes, completed, or empty for all
    public ?int $organizationId = null;
    public ?int $issueId = null;
    public string $completedPeriod = 'month'; // week, month, quarter, year, all

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'organizationId' => ['except' => null],
        'issueId' => ['except' => null],
        'completedPeriod' => ['except' => 'month'],
    ];

    // Upcoming meetings (no pagination, show all)
    public function getUpcomingMeetingsProperty()
    {
        return Meeting::upcoming()
            ->with(['attendees', 'organizations', 'issues'])
            ->withCount('commitments')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organizationId, fn($q) => $q->whereHas('organizations', fn($o) => $o->where('organizations.id', $this->organizationId)))
            ->when($this->issueId, fn($q) => $q->whereHas('issues', fn($i) => $i->where('issues.id', $this->issueId)))
            ->get();
    }

    // Meetings needing notes
    public function getNeedsNotesMeetingsProperty()
    {
        return Meeting::needsNotes()
            ->with(['attendees', 'organizations'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organizationId, fn($q) => $q->whereHas('organizations', fn($o) => $o->where('organizations.id', $this->organizationId)))
            ->when($this->issueId, fn($q) => $q->whereHas('issues', fn($i) => $i->where('issues.id', $this->issueId)))
            ->limit(10)
            ->get();
    }

    // Completed meetings with notes
    public function getCompletedMeetingsProperty()
    {
        $query = Meeting::withNotes()
            ->with(['attendees', 'organizations', 'issues'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organizationId, fn($q) => $q->whereHas('organizations', fn($o) => $o->where('organizations.id', $this->organizationId)))
            ->when($this->issueId, fn($q) => $q->whereHas('issues', fn($i) => $i->where('issues.id', $this->issueId)));

        // Period filter
        $query->when($this->completedPeriod === 'week', fn($q) => $q->where('scheduled_at', '>=', now()->subWeek()))
              ->when($this->completedPeriod === 'month', fn($q) => $q->where('scheduled_at', '>=', now()->subMonth()))
              ->when($this->completedPeriod === 'quarter', fn($q) => $q->where('scheduled_at', '>=', now()->subQuarter()))
              ->when($this->completedPeriod === 'year', fn($q) => $q->where('scheduled_at', '>=', now()->subYear()));

        return $query->paginate(10);
    }

    // Stats for section headers
    public function getStatsProperty()
    {
        return [
            'upcoming' => Meeting::upcoming()->count(),
            'needs_notes' => Meeting::needsNotes()->count(),
            'completed_this_month' => Meeting::withNotes()->where('scheduled_at', '>=', now()->subMonth())->count(),
        ];
    }

    // For filter dropdowns
    public function getOrganizationsProperty()
    {
        return Organization::orderBy('name')->get();
    }

    public function getIssuesProperty()
    {
        return Issue::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.meetings.index', [
            'upcomingMeetings' => $this->upcomingMeetings,
            'needsNotesMeetings' => $this->needsNotesMeetings,
            'completedMeetings' => $this->completedMeetings,
            'stats' => $this->stats,
            'organizations' => $this->organizations,
            'issues' => $this->issues,
        ]);
    }
}
```

---

## Main View Template

### resources/views/livewire/meetings/index.blade.php

```blade
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meetings</h1>
            <p class="text-gray-600">Track and manage all your stakeholder meetings and conversations</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                <x-heroicon-o-arrow-up-tray class="w-5 h-5" />
                Bulk Import
            </button>
            <a href="{{ route('meetings.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <x-heroicon-o-plus class="w-5 h-5" />
                Log Meeting
            </a>
        </div>
    </div>

    {{-- Calendar Sync Status --}}
    @if(auth()->user()->hasGoogleCalendarConnected())
    <div class="flex items-center justify-between p-3 mb-6 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <x-heroicon-o-calendar class="w-5 h-5 text-gray-400" />
            <span>Google Calendar connected</span>
            <span class="text-gray-400">•</span>
            <span>Last synced: {{ auth()->user()->calendar_last_synced_at?->diffForHumans() ?? 'Never' }}</span>
        </div>
        <button wire:click="syncCalendar" class="text-sm text-indigo-600 hover:text-indigo-800">
            Sync Now
        </button>
    </div>
    @else
    <div class="flex items-center justify-between p-3 mb-6 bg-amber-50 rounded-lg border border-amber-200">
        <div class="flex items-center gap-2 text-sm text-amber-800">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500" />
            <span>Connect Google Calendar to automatically import meetings</span>
        </div>
        <a href="{{ route('settings.calendar') }}" class="text-sm font-medium text-amber-800 hover:text-amber-900">
            Connect →
        </a>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4 mb-6">
        {{-- Search --}}
        <div class="flex-1 min-w-[200px] max-w-md">
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search meetings..."
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        {{-- Organization Filter --}}
        <select wire:model.live="organizationId" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Organizations</option>
            @foreach($organizations as $org)
                <option value="{{ $org->id }}">{{ $org->name }}</option>
            @endforeach
        </select>

        {{-- Issue Filter --}}
        <select wire:model.live="issueId" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Issues</option>
            @foreach($issues as $issue)
                <option value="{{ $issue->id }}">{{ $issue->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Quick Filter Tabs --}}
    <div class="flex items-center gap-1 p-1 mb-6 bg-gray-100 rounded-lg w-fit">
        <button wire:click="$set('status', '')"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $status === '' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
            All
        </button>
        <button wire:click="$set('status', 'upcoming')"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'upcoming' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
            Upcoming
            @if($stats['upcoming'] > 0)
                <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">{{ $stats['upcoming'] }}</span>
            @endif
        </button>
        <button wire:click="$set('status', 'needs_notes')"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'needs_notes' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
            Needs Notes
            @if($stats['needs_notes'] > 0)
                <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['needs_notes'] }}</span>
            @endif
        </button>
        <button wire:click="$set('status', 'completed')"
                class="px-4 py-2 text-sm font-medium rounded-md transition {{ $status === 'completed' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
            Completed
        </button>
    </div>

    {{-- Content Sections --}}
    <div class="space-y-8">

        {{-- UPCOMING SECTION --}}
        @if($status === '' || $status === 'upcoming')
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-heroicon-o-calendar class="w-5 h-5 text-indigo-500" />
                    Upcoming
                    <span class="text-sm font-normal text-gray-500">({{ $upcomingMeetings->count() }})</span>
                </h2>
            </div>

            @if($upcomingMeetings->count() > 0)
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($upcomingMeetings as $meeting)
                        @include('livewire.meetings.partials.upcoming-card', ['meeting' => $meeting])
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center bg-gray-50 rounded-lg border border-dashed border-gray-300">
                    <x-heroicon-o-calendar class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                    <h3 class="text-sm font-medium text-gray-900 mb-1">No upcoming meetings</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Meetings from Google Calendar will appear here, or you can manually log a meeting.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        @unless(auth()->user()->hasGoogleCalendarConnected())
                            <a href="{{ route('settings.calendar') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                Connect Google Calendar
                            </a>
                            <span class="text-gray-300">|</span>
                        @endunless
                        <a href="{{ route('meetings.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                            Log a meeting
                        </a>
                    </div>
                </div>
            @endif
        </section>
        @endif

        {{-- NEEDS NOTES SECTION --}}
        @if(($status === '' || $status === 'needs_notes') && $needsNotesMeetings->count() > 0)
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-heroicon-o-clock class="w-5 h-5 text-amber-500" />
                    Needs Notes
                    <span class="text-sm font-normal text-gray-500">({{ $stats['needs_notes'] }})</span>
                </h2>
                @if($stats['needs_notes'] > 10)
                    <a href="?status=needs_notes" class="text-sm text-indigo-600 hover:text-indigo-800">
                        View all →
                    </a>
                @endif
            </div>

            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                @foreach($needsNotesMeetings as $meeting)
                    @include('livewire.meetings.partials.needs-notes-row', ['meeting' => $meeting])
                @endforeach
            </div>
        </section>
        @endif

        {{-- COMPLETED SECTION --}}
        @if($status === '' || $status === 'completed')
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                    Completed
                </h2>
                <select wire:model.live="completedPeriod" class="text-sm border-gray-300 rounded-lg">
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                    <option value="all">All Time</option>
                </select>
            </div>

            @if($completedMeetings->count() > 0)
                <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                    @foreach($completedMeetings as $meeting)
                        @include('livewire.meetings.partials.completed-row', ['meeting' => $meeting])
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($completedMeetings->hasPages())
                    <div class="mt-4">
                        {{ $completedMeetings->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center bg-gray-50 rounded-lg border border-dashed border-gray-300">
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                    <p class="text-sm text-gray-500">No completed meetings with notes in this period.</p>
                </div>
            @endif
        </section>
        @endif

    </div>
</div>
```

---

## Partial: Upcoming Card

### resources/views/livewire/meetings/partials/upcoming-card.blade.php

```blade
@php
    $isToday = $meeting->scheduled_at->isToday();
    $isTomorrow = $meeting->scheduled_at->isTomorrow();
    $isThisWeek = $meeting->scheduled_at->isCurrentWeek();
    
    $dateLabel = match(true) {
        $isToday => 'Today',
        $isTomorrow => 'Tomorrow',
        $isThisWeek => $meeting->scheduled_at->format('l'), // Day name
        default => $meeting->scheduled_at->format('M j'),
    };
    
    $openCommitments = $meeting->open_commitments_count ?? 0;
@endphp

<div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
    {{-- Date Banner --}}
    <div class="px-4 py-2 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium {{ $isToday ? 'text-indigo-700' : 'text-gray-700' }}">
                {{ $dateLabel }}, {{ $meeting->scheduled_at->format('g:i A') }}
            </span>
            @if($isToday)
                <span class="px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full">Today</span>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="p-4">
        {{-- Title --}}
        <a href="{{ route('meetings.show', $meeting) }}" class="block group">
            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                {{ $meeting->title }}
            </h3>
        </a>

        {{-- Attendees --}}
        @if($meeting->attendees->count() > 0)
            <div class="flex items-center gap-2 mt-2 text-sm text-gray-600">
                <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                @if($meeting->attendees->count() <= 2)
                    {{ $meeting->attendees->pluck('name')->join(', ') }}
                @else
                    {{ $meeting->attendees->first()->name }} + {{ $meeting->attendees->count() - 1 }} others
                @endif
            </div>
        @endif

        {{-- Organizations --}}
        @if($meeting->organizations->count() > 0)
            <div class="flex items-center gap-2 mt-1 text-sm text-gray-600">
                <x-heroicon-o-building-office class="w-4 h-4 text-gray-400" />
                {{ $meeting->organizations->pluck('name')->join(', ') }}
            </div>
        @endif

        {{-- Issues/Topics --}}
        @if($meeting->issues->count() > 0)
            <div class="flex items-center gap-2 mt-2">
                @foreach($meeting->issues->take(3) as $issue)
                    <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                        {{ $issue->name }}
                    </span>
                @endforeach
                @if($meeting->issues->count() > 3)
                    <span class="text-xs text-gray-400">+{{ $meeting->issues->count() - 3 }} more</span>
                @endif
            </div>
        @endif

        {{-- Open Commitments Alert --}}
        @if($openCommitments > 0)
            <div class="flex items-center gap-2 mt-3 p-2 bg-amber-50 rounded-lg text-sm text-amber-800">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-500" />
                {{ $openCommitments }} open commitment{{ $openCommitments > 1 ? 's' : '' }} to discuss
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-gray-600 hover:text-gray-900">
            View details
        </a>
        <a href="{{ route('meetings.prep', $meeting) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
            <x-heroicon-o-document-text class="w-4 h-4" />
            Prep
        </a>
    </div>
</div>
```

---

## Partial: Needs Notes Row

### resources/views/livewire/meetings/partials/needs-notes-row.blade.php

```blade
<div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
    <div class="flex items-center gap-4 flex-1 min-w-0">
        {{-- Org Avatar --}}
        <div class="flex-shrink-0">
            @if($meeting->organizations->first()?->logo_url)
                <img src="{{ $meeting->organizations->first()->logo_url }}" 
                     alt="{{ $meeting->organizations->first()->name }}"
                     class="w-10 h-10 rounded-lg object-cover">
            @else
                <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center">
                    <x-heroicon-o-building-office class="w-5 h-5 text-gray-400" />
                </div>
            @endif
        </div>

        {{-- Meeting Info --}}
        <div class="flex-1 min-w-0">
            <a href="{{ route('meetings.show', $meeting) }}" class="block">
                <h4 class="font-medium text-gray-900 truncate hover:text-indigo-600 transition-colors">
                    {{ $meeting->title }}
                </h4>
            </a>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                @if($meeting->attendees->count() > 0)
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-user class="w-3.5 h-3.5" />
                        {{ $meeting->attendees->count() }} attendee{{ $meeting->attendees->count() > 1 ? 's' : '' }}
                    </span>
                @endif
                @if($meeting->organizations->first())
                    <span class="text-gray-300">•</span>
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-building-office class="w-3.5 h-3.5" />
                        {{ $meeting->organizations->first()->name }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Date --}}
    <div class="flex-shrink-0 text-sm text-gray-500 mx-4">
        {{ $meeting->scheduled_at->format('M j') }}
    </div>

    {{-- Action --}}
    <a href="{{ route('meetings.edit', $meeting) }}#notes"
       class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
        <x-heroicon-o-pencil-square class="w-4 h-4" />
        Add Notes
    </a>
</div>
```

---

## Partial: Completed Row

### resources/views/livewire/meetings/partials/completed-row.blade.php

```blade
<div class="px-4 py-3 hover:bg-gray-50 transition-colors">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 flex-1 min-w-0">
            {{-- Org Avatar --}}
            <div class="flex-shrink-0">
                @if($meeting->organizations->first()?->logo_url)
                    <img src="{{ $meeting->organizations->first()->logo_url }}" 
                         alt="{{ $meeting->organizations->first()->name }}"
                         class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center">
                        <x-heroicon-o-building-office class="w-5 h-5 text-gray-400" />
                    </div>
                @endif
            </div>

            {{-- Meeting Info --}}
            <div class="flex-1 min-w-0">
                <a href="{{ route('meetings.show', $meeting) }}" class="block">
                    <h4 class="font-medium text-gray-900 truncate hover:text-indigo-600 transition-colors">
                        {{ $meeting->title }}
                    </h4>
                </a>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    @if($meeting->attendees->count() > 0)
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-user class="w-3.5 h-3.5" />
                            {{ $meeting->attendees->count() }}
                        </span>
                    @endif
                    @if($meeting->organizations->first())
                        <span class="text-gray-300">•</span>
                        <span>{{ $meeting->organizations->first()->name }}</span>
                    @endif
                    @if($meeting->issues->count() > 0)
                        <span class="text-gray-300">•</span>
                        @foreach($meeting->issues->take(2) as $issue)
                            <span class="px-1.5 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">
                                {{ $issue->name }}
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Date --}}
        <div class="flex-shrink-0 text-sm text-gray-500 mx-4">
            {{ $meeting->scheduled_at->format('M j') }}
        </div>

        {{-- Has Notes Indicator --}}
        <div class="flex-shrink-0">
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-full">
                <x-heroicon-s-check class="w-3 h-3" />
                Notes
            </span>
        </div>
    </div>

    {{-- Notes Preview --}}
    @if($meeting->notes_preview)
        <div class="mt-2 ml-14 text-sm text-gray-600 italic">
            "{{ $meeting->notes_preview }}"
        </div>
    @endif
</div>
```

---

## Routes

Ensure these routes exist:

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {
    // Meetings
    Route::get('/meetings', \App\Livewire\Meetings\Index::class)->name('meetings.index');
    Route::get('/meetings/create', \App\Livewire\Meetings\Create::class)->name('meetings.create');
    Route::get('/meetings/{meeting}', \App\Livewire\Meetings\Show::class)->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', \App\Livewire\Meetings\Edit::class)->name('meetings.edit');
    Route::get('/meetings/{meeting}/prep', \App\Livewire\Meetings\Prep::class)->name('meetings.prep');
});
```

---

## Meeting Prep Page (New)

The [Prep] button leads to a dedicated prep page that uses the Knowledge Hub to generate context.

### app/Livewire/Meetings/Prep.php

```php
<?php

namespace App\Livewire\Meetings;

use App\Models\Meeting;
use App\Services\KnowledgeHubService;
use Livewire\Component;

class Prep extends Component
{
    public Meeting $meeting;
    public array $prep = [];
    public bool $loading = true;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting->load([
            'attendees.organization',
            'organizations',
            'issues',
            'projects',
        ]);
    }

    public function loadPrep()
    {
        $service = app(KnowledgeHubService::class);
        
        $this->prep = $service->generateMeetingPrep($this->meeting);
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.meetings.prep');
    }
}
```

### resources/views/livewire/meetings/prep.blade.php

```blade
<div wire:init="loadPrep">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('meetings.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
            ← Back to Meetings
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Meeting Prep</h1>
        <p class="text-gray-600">{{ $meeting->title }}</p>
        <p class="text-sm text-gray-500">
            {{ $meeting->scheduled_at->format('l, F j, Y \a\t g:i A') }}
        </p>
    </div>

    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="flex items-center gap-3 text-gray-500">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Generating prep materials...
            </div>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Attendees --}}
                <section class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Attendees</h2>
                    <div class="space-y-4">
                        @foreach($meeting->attendees as $attendee)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium">
                                    {{ $attendee->initials }}
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $attendee->name }}</h3>
                                    @if($attendee->title)
                                        <p class="text-sm text-gray-600">{{ $attendee->title }}</p>
                                    @endif
                                    @if($attendee->organization)
                                        <p class="text-sm text-gray-500">{{ $attendee->organization->name }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $attendee->meetings_count ?? 0 }} previous meetings
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Relationship History --}}
                @if(isset($prep['relationship_history']))
                <section class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Relationship History</h2>
                    <div class="prose prose-sm max-w-none text-gray-600">
                        {!! $prep['relationship_history'] !!}
                    </div>
                </section>
                @endif

                {{-- Last Meeting --}}
                @if(isset($prep['last_meeting']))
                <section class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Last Meeting</h2>
                    <div class="text-sm text-gray-500 mb-2">
                        {{ $prep['last_meeting']['date'] }}
                    </div>
                    <div class="prose prose-sm max-w-none text-gray-600">
                        {!! $prep['last_meeting']['summary'] !!}
                    </div>
                </section>
                @endif

                {{-- Suggested Talking Points --}}
                @if(isset($prep['talking_points']))
                <section class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Suggested Talking Points</h2>
                    <ul class="space-y-2">
                        @foreach($prep['talking_points'] as $point)
                            <li class="flex items-start gap-2">
                                <x-heroicon-s-chat-bubble-left class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" />
                                <span class="text-gray-700">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Open Commitments --}}
                @if(isset($prep['open_commitments']) && count($prep['open_commitments']) > 0)
                <section class="bg-amber-50 rounded-lg border border-amber-200 p-4">
                    <h3 class="font-semibold text-amber-900 mb-3 flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500" />
                        Open Commitments
                    </h3>
                    <ul class="space-y-2">
                        @foreach($prep['open_commitments'] as $commitment)
                            <li class="text-sm text-amber-800">
                                <span class="font-medium">{{ $commitment['direction'] }}:</span>
                                {{ $commitment['description'] }}
                                @if($commitment['due_date'])
                                    <span class="text-amber-600">(Due: {{ $commitment['due_date'] }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
                @endif

                {{-- Organizations --}}
                <section class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Organizations</h3>
                    <div class="space-y-2">
                        @foreach($meeting->organizations as $org)
                            <a href="{{ route('organizations.show', $org) }}" 
                               class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-600">
                                <x-heroicon-o-building-office class="w-4 h-4 text-gray-400" />
                                {{ $org->name }}
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Related Projects --}}
                @if($meeting->projects->count() > 0)
                <section class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Related Projects</h3>
                    <div class="space-y-2">
                        @foreach($meeting->projects as $project)
                            <a href="{{ route('projects.show', $project) }}" 
                               class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-600">
                                <x-heroicon-o-folder class="w-4 h-4 text-gray-400" />
                                {{ $project->name }}
                            </a>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- Issues --}}
                @if($meeting->issues->count() > 0)
                <section class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Topics</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($meeting->issues as $issue)
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                                {{ $issue->name }}
                            </span>
                        @endforeach
                    </div>
                </section>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex items-center gap-4">
            <button onclick="window.print()" 
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                <x-heroicon-o-printer class="w-5 h-5" />
                Print
            </button>
            <a href="{{ route('meetings.show', $meeting) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Go to Meeting
            </a>
        </div>
    @endif
</div>
```

---

## Summary of Changes

1. **New Livewire Component**: `Meetings/Index.php` with three-section data
2. **New Main View**: `meetings/index.blade.php` with tabs and sections
3. **New Partials**:
   - `upcoming-card.blade.php` - Rich cards for upcoming meetings
   - `needs-notes-row.blade.php` - Action-oriented rows for meetings needing notes
   - `completed-row.blade.php` - Completed meetings with notes preview
4. **New Meeting Prep Page**: `Meetings/Prep.php` and view
5. **Model Updates**: Scopes and helper methods on Meeting model
6. **Calendar Sync Status**: Visual indicator of sync state

## Testing Checklist

- [ ] Upcoming section shows future meetings
- [ ] Needs Notes section shows past meetings without notes
- [ ] Completed section shows past meetings with notes
- [ ] Notes preview displays correctly
- [ ] Quick filter tabs work
- [ ] Organization/Issue filters work
- [ ] Completed period filter works
- [ ] [Prep] button leads to prep page
- [ ] [Add Notes] button leads to edit page with notes section
- [ ] Empty states display correctly
- [ ] Calendar sync status shows correctly
- [ ] Mobile responsive layout works
