<?php

namespace App\Livewire\CongressionalDirectory;

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalStaffEmail;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Congressional Outreach Dry Run')]
class OutreachDraftShow extends Component
{
    use WithPagination;

    public CongressionalOutreachDraft $draft;

    public string $subject = '';

    public string $bodyText = '';

    public string $recipientSearch = '';

    public string $statusFilter = 'all';

    public ?int $previewRecipientId = null;

    public ?int $selectedViewerId = null;

    public bool $canManage = false;

    public function mount(CongressionalOutreachDraft $draft): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
        abort_unless($draft->canBeViewedBy(Auth::user()), 404);

        $this->draft = $draft;
        $this->canManage = $draft->canBeManagedBy(Auth::user());
        $this->subject = (string) $draft->subject;
        $this->bodyText = (string) $draft->body_text;
        $this->previewRecipientId = $draft->recipients()
            ->orderByRaw("CASE review_status WHEN 'approved' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->value('id');
    }

    public function updatedRecipientSearch(): void
    {
        $this->resetPage('recipientPage');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('recipientPage');
    }

    public function saveMessage(CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'bodyText' => ['required', 'string', 'max:50000'],
        ]);

        $workbench->updateMessage($this->draft, $this->subject, $this->bodyText);
        $this->draft->refresh();
        $this->dispatch('notify', type: 'success', message: 'Dry-run message saved. No email was sent.');
    }

    public function refreshSnapshot(): void
    {
        $this->authorizeManage();
        $this->draft->refresh();
        if ($this->draft->status === 'building') {
            $this->dispatch('notify', type: 'info', message: 'The recipient snapshot is already building.');

            return;
        }

        $metadata = $this->draft->metadata ?? [];
        unset($metadata['snapshot_error'], $metadata['snapshot_failed_at']);
        $this->draft->update([
            'status' => 'building',
            'reviewed_at' => null,
            'metadata' => $metadata ?: null,
        ]);
        BuildCongressionalOutreachDraftSnapshot::dispatch($this->draft->id)->afterCommit();
        $this->draft->refresh();
        $this->dispatch('notify', type: 'success', message: 'Recipient snapshot is rebuilding in the background. Previous approvals will be reset.');
    }

    public function selectEmail(
        int $recipientId,
        int $staffEmailId,
        CongressionalOutreachWorkbenchService $workbench
    ): void {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $recipient = $this->recipient($recipientId);
        $staffEmail = CongressionalStaffEmail::query()
            ->where('profile_id', $recipient->profile_id)
            ->findOrFail($staffEmailId);

        $this->attempt(fn () => $workbench->selectEmail($recipient, $staffEmail));
    }

    public function approveRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $this->attempt(fn () => $workbench->approve($this->recipient($recipientId), Auth::id()));
    }

    public function approveAllEligible(CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $count = $workbench->approveAllEligible($this->draft, Auth::id());
        $this->draft->refresh();
        $this->dispatch('notify', type: 'success', message: "Approved {$count} eligible recipients. Provisional addresses still require individual review.");
    }

    public function excludeRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $this->attempt(fn () => $workbench->exclude($this->recipient($recipientId), Auth::id()));
    }

    public function restoreRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $this->attempt(fn () => $workbench->restore($this->recipient($recipientId)));
    }

    public function showPreview(int $recipientId): void
    {
        $this->recipient($recipientId);
        $this->previewRecipientId = $recipientId;
    }

    public function markReady(CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->authorizeManage();
        if (! $this->snapshotIsReady()) {
            return;
        }

        $this->saveMessage($workbench);
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->attempt(fn () => $workbench->markReady($this->draft->fresh()));
    }

    public function deleteDraft(): mixed
    {
        $this->authorizeManage();
        $this->draft->delete();
        $this->dispatch('notify', type: 'success', message: 'Dry run deleted. No staff profiles were changed.');

        return $this->redirectRoute('congress.lists', navigate: true);
    }

    public function addViewer(): void
    {
        $this->authorizeManage();
        $this->validate([
            'selectedViewerId' => ['required', 'integer'],
        ]);

        $viewer = User::query()
            ->active()
            ->whereKey($this->selectedViewerId)
            ->where('id', '!=', $this->draft->user_id)
            ->first();

        if (! $viewer) {
            $this->addError('selectedViewerId', 'Choose an active team member.');

            return;
        }

        $this->draft->viewers()->syncWithoutDetaching([
            $viewer->id => ['added_by' => Auth::id()],
        ]);
        $this->selectedViewerId = null;
        $this->dispatch('notify', type: 'success', message: "{$viewer->name} can now view this campaign.");
    }

    public function removeViewer(int $userId): void
    {
        $this->authorizeManage();
        $viewer = $this->draft->viewers()->whereKey($userId)->first();
        if (! $viewer) {
            return;
        }

        $this->draft->viewers()->detach($viewer->id);
        $this->dispatch('notify', type: 'success', message: "{$viewer->name} no longer has access to this campaign.");
    }

    protected function recipient(int $recipientId): CongressionalOutreachDraftRecipient
    {
        return $this->draft->recipients()->findOrFail($recipientId);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->draft->canBeManagedBy(Auth::user()), 403);
    }

    protected function snapshotIsReady(): bool
    {
        $this->draft->refresh();
        if (in_array($this->draft->status, ['draft', 'ready'], true)) {
            return true;
        }

        $message = $this->draft->status === 'failed'
            ? 'Retry the recipient snapshot before making review changes.'
            : 'Wait for the recipient snapshot to finish building before making changes.';
        $this->dispatch('notify', type: 'info', message: $message);

        return false;
    }

    protected function attempt(callable $action): void
    {
        try {
            $action();
            $this->draft->refresh();
        } catch (DomainException $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
    }

    public function render(CongressionalOutreachWorkbenchService $workbench)
    {
        $previousStatus = $this->draft->status;
        $this->draft->refresh();
        abort_unless($this->draft->canBeViewedBy(Auth::user()), 404);

        if ($previousStatus === 'building' && $this->draft->status === 'draft' && ! $this->previewRecipientId) {
            $this->previewRecipientId = $this->draft->recipients()->orderBy('id')->value('id');
        }

        $recipients = $this->draft->recipients()
            ->with(['profile.emails' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('email')])
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                if (in_array($this->statusFilter, ['approved', 'pending', 'excluded'], true)) {
                    $query->where('review_status', $this->statusFilter);
                } elseif (in_array($this->statusFilter, ['eligible', 'limited', 'blocked'], true)) {
                    $query->where('eligibility_tier', $this->statusFilter);
                }
            })
            ->when(trim($this->recipientSearch) !== '', function (Builder $query): void {
                $term = '%'.trim($this->recipientSearch).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereLike('name', $term)
                        ->orWhereLike('email', $term)
                        ->orWhereLike('title', $term)
                        ->orWhereLike('office', $term);
                });
            })
            ->orderByRaw("CASE review_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(25, ['*'], 'recipientPage');

        $previewRecipient = $this->previewRecipientId
            ? $this->draft->recipients()->find($this->previewRecipientId)
            : null;
        $viewers = $this->draft->viewers()->orderBy('name')->get();
        $availableViewers = $this->canManage
            ? User::query()
                ->active()
                ->where('id', '!=', $this->draft->user_id)
                ->whereNotIn('id', $viewers->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        return view('livewire.congressional-directory.outreach-draft-show', [
            'recipients' => $recipients,
            'summary' => $workbench->summary($this->draft),
            'previewRecipient' => $previewRecipient,
            'preview' => $previewRecipient ? $workbench->preview($this->draft, $previewRecipient) : null,
            'viewers' => $viewers,
            'availableViewers' => $availableViewers,
            'reasonLabels' => [
                'inactive_profile' => 'No current position',
                'no_address' => 'No address available',
                'blocked_address' => 'Address is suppressed or blocked',
                'duplicate_address' => 'Duplicate address in this dry run',
                'manual_exclusion' => 'Removed during review',
            ],
        ]);
    }
}
