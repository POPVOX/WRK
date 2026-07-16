<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachRecipientEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Campaign Analytics')]
class OutreachAnalytics extends Component
{
    use WithPagination;

    public CongressionalOutreachDraft $draft;

    public string $recipientSearch = '';

    public string $outcomeFilter = 'all';

    public function mount(CongressionalOutreachDraft $draft): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
        abort_unless($draft->canBeViewedBy(Auth::user()), 404);
        $this->draft = $draft;
    }

    public function updatedRecipientSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOutcomeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->draft->refresh();
        abort_unless($this->draft->canBeViewedBy(Auth::user()), 404);

        $base = $this->recipientQuery();
        $sent = (clone $base)->where('status', 'sent')->count();
        $opened = (clone $base)->whereNotNull('opened_at')->count();
        $clicked = (clone $base)->whereNotNull('clicked_at')->count();
        $replied = (clone $base)->whereNotNull('replied_at')->count();
        $bounced = (clone $base)->whereNotNull('bounced_at')->count();
        $unsubscribed = (clone $base)->whereNotNull('unsubscribed_at')->count();

        $recipients = $this->applyFilters($this->recipientQuery())
            ->with([
                'campaign:id,name,status,launched_at',
                'congressionalOutreachDraftRecipient.profile.currentPosition.office',
                'events' => fn ($query) => $query->limit(8),
            ])
            ->latest('id')
            ->paginate(50);

        $topLinks = OutreachRecipientEvent::query()
            ->where('event_type', 'click')
            ->whereHas('recipient.campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $this->draft->id))
            ->selectRaw('url, COUNT(*) as clicks, COUNT(DISTINCT campaign_recipient_id) as unique_recipients')
            ->groupBy('url')
            ->orderByDesc('unique_recipients')
            ->limit(10)
            ->get();

        return view('livewire.congressional-directory.outreach-analytics', [
            'recipients' => $recipients,
            'campaigns' => $this->draft->outreachCampaigns()->latest('id')->get(),
            'topLinks' => $topLinks,
            'metrics' => [
                'total' => (clone $base)->count(),
                'sent' => $sent,
                'opened' => $opened,
                'clicked' => $clicked,
                'replied' => $replied,
                'bounced' => $bounced,
                'unsubscribed' => $unsubscribed,
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'suppressed' => (clone $base)->where('status', 'suppressed')->count(),
                'open_rate' => $this->rate($opened, $sent),
                'click_rate' => $this->rate($clicked, $sent),
                'click_through_rate' => $this->rate($clicked, $opened),
                'reply_rate' => $this->rate($replied, $sent),
                'bounce_rate' => $this->rate($bounced, $sent),
            ],
        ]);
    }

    protected function recipientQuery(): Builder
    {
        return OutreachCampaignRecipient::query()
            ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $this->draft->id));
    }

    protected function applyFilters(Builder $query): Builder
    {
        $query->when(trim($this->recipientSearch) !== '', function (Builder $query): void {
            $term = '%'.trim($this->recipientSearch).'%';
            $query->where(fn (Builder $query) => $query
                ->whereLike('name', $term)
                ->orWhereLike('email', $term));
        });

        return match ($this->outcomeFilter) {
            'sent' => $query->where('status', 'sent'),
            'opened' => $query->whereNotNull('opened_at'),
            'clicked' => $query->whereNotNull('clicked_at'),
            'replied' => $query->whereNotNull('replied_at'),
            'bounced' => $query->whereNotNull('bounced_at'),
            'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
            'failed' => $query->where('status', 'failed'),
            'suppressed' => $query->where('status', 'suppressed'),
            'untracked' => $query->whereNull('tracking_token'),
            default => $query,
        };
    }

    protected function rate(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
    }
}
