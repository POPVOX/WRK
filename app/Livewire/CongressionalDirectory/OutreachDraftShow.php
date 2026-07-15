<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalStaffEmail;
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

    public function mount(CongressionalOutreachDraft $draft): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
        abort_unless($draft->user_id === Auth::id(), 404);

        $this->draft = $draft;
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
        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'bodyText' => ['required', 'string', 'max:50000'],
        ]);

        $workbench->updateMessage($this->draft, $this->subject, $this->bodyText);
        $this->draft->refresh();
        $this->dispatch('notify', type: 'success', message: 'Dry-run message saved. No email was sent.');
    }

    public function refreshSnapshot(CongressionalOutreachWorkbenchService $workbench): void
    {
        $count = $workbench->refreshSnapshot($this->draft);
        $this->draft->refresh();
        $this->previewRecipientId = $this->draft->recipients()->orderBy('id')->value('id');
        $this->dispatch('notify', type: 'success', message: "Recipient snapshot refreshed with {$count} staff members. Previous approvals were reset.");
    }

    public function selectEmail(
        int $recipientId,
        int $staffEmailId,
        CongressionalOutreachWorkbenchService $workbench
    ): void {
        $recipient = $this->recipient($recipientId);
        $staffEmail = CongressionalStaffEmail::query()
            ->where('profile_id', $recipient->profile_id)
            ->findOrFail($staffEmailId);

        $this->attempt(fn () => $workbench->selectEmail($recipient, $staffEmail));
    }

    public function approveRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->attempt(fn () => $workbench->approve($this->recipient($recipientId), Auth::id()));
    }

    public function approveAllEligible(CongressionalOutreachWorkbenchService $workbench): void
    {
        $count = $workbench->approveAllEligible($this->draft, Auth::id());
        $this->draft->refresh();
        $this->dispatch('notify', type: 'success', message: "Approved {$count} eligible recipients. Provisional addresses still require individual review.");
    }

    public function excludeRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->attempt(fn () => $workbench->exclude($this->recipient($recipientId), Auth::id()));
    }

    public function restoreRecipient(int $recipientId, CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->attempt(fn () => $workbench->restore($this->recipient($recipientId)));
    }

    public function showPreview(int $recipientId): void
    {
        $this->recipient($recipientId);
        $this->previewRecipientId = $recipientId;
    }

    public function markReady(CongressionalOutreachWorkbenchService $workbench): void
    {
        $this->saveMessage($workbench);
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->attempt(fn () => $workbench->markReady($this->draft->fresh()));
    }

    public function deleteDraft(): mixed
    {
        $this->draft->delete();
        $this->dispatch('notify', type: 'success', message: 'Dry run deleted. No staff profiles were changed.');

        return $this->redirectRoute('congress.lists', navigate: true);
    }

    protected function recipient(int $recipientId): CongressionalOutreachDraftRecipient
    {
        return $this->draft->recipients()->findOrFail($recipientId);
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

        return view('livewire.congressional-directory.outreach-draft-show', [
            'recipients' => $recipients,
            'summary' => $workbench->summary($this->draft),
            'previewRecipient' => $previewRecipient,
            'preview' => $previewRecipient ? $workbench->preview($this->draft, $previewRecipient) : null,
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
