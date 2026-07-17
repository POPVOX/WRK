<?php

namespace App\Livewire\CongressionalDirectory;

use App\Jobs\BuildCongressionalOutreachDraftSnapshot;
use App\Models\CongressionalOutreachDraft;
use App\Services\CongressionalDirectory\CongressionalCampaignScheduleService;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Congressional Campaigns')]
class CampaignIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function duplicateCampaign(
        int $draftId,
        CongressionalOutreachWorkbenchService $workbench
    ): mixed {
        $source = CongressionalOutreachDraft::query()
            ->where('user_id', Auth::id())
            ->findOrFail($draftId);

        $copy = $workbench->duplicateDraft($source, Auth::id());
        BuildCongressionalOutreachDraftSnapshot::dispatch($copy->id)->afterCommit();
        $this->dispatch('notify', type: 'success', message: 'Campaign duplicated. Review the audience, message, and delivery settings before sending.');

        return $this->redirectRoute('congress.outreach.show', ['draft' => $copy], navigate: true);
    }

    public function pauseCampaign(
        int $draftId,
        CongressionalCampaignScheduleService $schedules
    ): void {
        $draft = CongressionalOutreachDraft::query()
            ->where('user_id', Auth::id())
            ->findOrFail($draftId);

        $schedules->pause($draft, Auth::user());
        $this->dispatch('notify', type: 'success', message: 'Campaign paused. No new automated batches will start until it is resumed.');
    }

    public function render()
    {
        $campaigns = CongressionalOutreachDraft::query()
            ->where(function (Builder $query): void {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('viewers', fn (Builder $query) => $query->where('users.id', Auth::id()));
            })
            ->with(['staffList:id,name', 'user:id,name'])
            ->withCount([
                'recipients',
                'recipients as approved_recipients_count' => fn (Builder $query) => $query->where('review_status', 'approved'),
                'outreachRecipients as sent_recipients_count' => fn (Builder $query) => $query->where('outreach_campaign_recipients.status', 'sent'),
                'outreachRecipients as failed_recipients_count' => fn (Builder $query) => $query->where('outreach_campaign_recipients.status', 'failed'),
            ])
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $term)
                    ->orWhereLike('subject', $term)
                    ->orWhereHas('staffList', fn (Builder $query) => $query->whereLike('name', $term)));
            })
            ->when($this->status !== 'all', function (Builder $query): void {
                if ($this->status === 'active') {
                    $query->where('schedule_status', 'active');
                } elseif ($this->status === 'completed') {
                    $query->where('schedule_status', 'completed');
                } elseif ($this->status === 'draft') {
                    $query->whereIn('schedule_status', ['inactive', 'paused']);
                }
            })
            ->latest()
            ->paginate(25);

        return view('livewire.congressional-directory.campaign-index', compact('campaigns'));
    }
}
