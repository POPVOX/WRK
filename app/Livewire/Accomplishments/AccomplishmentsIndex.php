<?php

namespace App\Livewire\Accomplishments;

use App\Jobs\CalculateUserAccomplishments;
use App\Models\Accomplishment;
use App\Models\AccomplishmentReaction;
use App\Models\Project;
use App\Models\User;
use App\Models\UserActivityStats;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Accomplishments')]
class AccomplishmentsIndex extends Component
{
    use WithFileUploads, WithPagination;

    // URL-bound filters
    #[Url]
    public string $period = 'month';

    #[Url]
    public string $type = '';

    #[Url]
    public string $visibility = '';

    // View state
    public string $viewMode = 'list'; // list or timeline

    public ?int $viewingUserId = null;

    public ?User $viewingUser = null;

    public bool $isOwnProfile = true;

    // Stats
    public ?UserActivityStats $stats = null;

    // Modal state
    public bool $showAddModal = false;

    public bool $showRecognizeModal = false;

    // Add accomplishment form
    public string $newTitle = '';

    public string $newDescription = '';

    public string $newType = 'milestone';

    public string $newVisibility = 'team';

    public ?string $newDate = null;

    public string $newSource = '';

    public array $newContributors = [];

    public ?int $newProjectId = null;

    public ?int $newGrantId = null;

    public $newAttachment;

    // Recognize form
    public ?int $recognizeUserId = null;

    public string $recognizeTitle = '';

    public string $recognizeDescription = '';

    public bool $recognizePublic = true;

    public function mount(?int $userId = null): void
    {
        $this->viewingUserId = $userId ?? Auth::id();
        $this->viewingUser = User::find($this->viewingUserId);
        $this->isOwnProfile = $this->viewingUserId === Auth::id();
        $this->newDate = now()->format('Y-m-d');

        // Check URL for add modal
        if (request()->has('add')) {
            $this->showAddModal = true;
        }

        $this->loadStats();
    }

    protected function loadStats(): void
    {
        [$startDate, $endDate] = $this->getPeriodDates();

        $job = new CalculateUserAccomplishments($this->viewingUserId, $startDate, $endDate);
        $this->stats = $job->handle();
    }

    protected function getPeriodDates(): array
    {
        return match ($this->period) {
            'week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
            'year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'all' => ['2020-01-01', now()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }

    public function updatedPeriod(): void
    {
        $this->loadStats();
        $this->resetPage();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadStats();
        $this->resetPage();
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->resetPage();
    }

    public function setVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
        $this->resetPage();
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'timeline' : 'list';
    }

    // === Add Accomplishment ===

    public function openAddModal(): void
    {
        $this->resetAddForm();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->resetAddForm();
    }

    protected function resetAddForm(): void
    {
        $this->newTitle = '';
        $this->newDescription = '';
        $this->newType = 'milestone';
        $this->newVisibility = 'team';
        $this->newDate = now()->format('Y-m-d');
        $this->newSource = '';
        $this->newContributors = [];
        $this->newProjectId = null;
        $this->newGrantId = null;
        $this->newAttachment = null;
    }

    public function saveAccomplishment(): void
    {
        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'nullable|string|max:5000',
            'newType' => 'required|in:'.implode(',', array_keys(Accomplishment::TYPES)),
            'newVisibility' => 'required|in:personal,team,organizational',
            'newDate' => 'required|date',
            'newSource' => 'nullable|string|max:255',
            'newProjectId' => 'nullable|exists:projects,id',
            'newAttachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentPath = null;
        if ($this->newAttachment) {
            $attachmentPath = $this->newAttachment->store('accomplishments', 'public');
        }

        // Format contributors
        $contributors = ! empty($this->newContributors)
            ? array_map(fn ($id) => ['user_id' => (int) $id, 'role' => 'collaborator'], $this->newContributors)
            : null;

        Accomplishment::create([
            'user_id' => Auth::id(),
            'title' => $this->newTitle,
            'description' => $this->newDescription,
            'type' => $this->newType,
            'visibility' => $this->newVisibility,
            'date' => $this->newDate,
            'source' => $this->newSource ?: null,
            'attachment_path' => $attachmentPath,
            'added_by' => Auth::id(),
            'is_recognition' => false,
            'contributors' => $contributors,
            'project_id' => $this->newProjectId,
            'grant_id' => $this->newGrantId,
        ]);

        $this->closeAddModal();
        $this->loadStats();
        $this->dispatch('notify', type: 'success', message: 'Accomplishment added! 🎉');
    }

    // === Recognition ===

    public function openRecognizeModal(?int $userId = null): void
    {
        $this->recognizeUserId = $userId ?? $this->viewingUserId;
        $this->showRecognizeModal = true;
    }

    public function closeRecognizeModal(): void
    {
        $this->showRecognizeModal = false;
        $this->recognizeUserId = null;
        $this->recognizeTitle = '';
        $this->recognizeDescription = '';
        $this->recognizePublic = true;
    }

    public function sendRecognition(): void
    {
        $this->validate([
            'recognizeUserId' => 'required|exists:users,id',
            'recognizeTitle' => 'required|string|max:255',
            'recognizeDescription' => 'required|string|max:2000',
        ]);

        if ($this->recognizeUserId === Auth::id()) {
            $this->dispatch('notify', type: 'error', message: 'You cannot recognize yourself.');

            return;
        }

        Accomplishment::create([
            'user_id' => $this->recognizeUserId,
            'title' => $this->recognizeTitle,
            'description' => $this->recognizeDescription,
            'type' => 'recognition',
            'visibility' => $this->recognizePublic ? 'team' : 'personal',
            'date' => now()->toDateString(),
            'added_by' => Auth::id(),
            'is_recognition' => true,
        ]);

        $this->closeRecognizeModal();
        $this->dispatch('notify', type: 'success', message: 'Recognition sent! 🌟');

        // TODO: Send notification to recognized user
    }

    // === Reactions ===

    public function react(int $accomplishmentId, string $reactionType): void
    {
        $accomplishment = Accomplishment::find($accomplishmentId);
        if (! $accomplishment) {
            return;
        }

        $existingReaction = AccomplishmentReaction::where('accomplishment_id', $accomplishmentId)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReaction) {
            if ($existingReaction->reaction_type === $reactionType) {
                // Toggle off
                $existingReaction->delete();
            } else {
                // Change reaction
                $existingReaction->update(['reaction_type' => $reactionType]);
            }
        } else {
            AccomplishmentReaction::create([
                'accomplishment_id' => $accomplishmentId,
                'user_id' => Auth::id(),
                'reaction_type' => $reactionType,
            ]);
        }
    }

    // === Delete ===

    public function deleteAccomplishment(int $id): void
    {
        $accomplishment = Accomplishment::find($id);

        if (! $accomplishment) {
            return;
        }

        // Can only delete own accomplishments or if admin
        if ($accomplishment->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'You cannot delete this accomplishment.');

            return;
        }

        if ($accomplishment->attachment_path) {
            Storage::disk('public')->delete($accomplishment->attachment_path);
        }

        $accomplishment->delete();
        $this->loadStats();
        $this->dispatch('notify', type: 'success', message: 'Accomplishment deleted.');
    }

    // === Export ===

    public function exportAccomplishments(): void
    {
        // TODO: Implement PDF/DOCX export
        $this->dispatch('notify', type: 'info', message: 'Export feature coming soon!');
    }

    public function render()
    {
        [$startDate, $endDate] = $this->getPeriodDates();

        $accomplishmentsQuery = Accomplishment::with(['addedBy', 'project', 'reactions.user'])
            ->where('user_id', $this->viewingUserId)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->visibility, fn ($q) => $q->where('visibility', $this->visibility))
            ->orderBy('date', 'desc');

        // Get team members for tagging
        $teamMembers = User::where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get available projects
        $projects = Project::whereIn('status', ['active', 'planning'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.accomplishments.accomplishments-index', [
            'accomplishments' => $accomplishmentsQuery->paginate(20),
            'teamMembers' => $teamMembers,
            'projects' => $projects,
            'types' => Accomplishment::TYPES,
            'reactionTypes' => AccomplishmentReaction::TYPES,
        ]);
    }
}

