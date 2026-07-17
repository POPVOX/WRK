<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\GmailMessage;
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
        $opened = (clone $base)->where('open_count', '>', 0)->count();
        $clicked = (clone $base)->whereNotNull('clicked_at')->count();
        $humanReplies = (clone $base)->whereHas(
            'emailEvidenceEvents',
            fn ($query) => $query->where('event_type', 'human_reply')
        )->count();
        $autoReplies = (clone $base)->whereHas(
            'emailEvidenceEvents',
            fn ($query) => $query->whereIn('event_type', ['auto_reply', 'departure_auto_reply'])
        )->count();
        $departureNotices = (clone $base)->whereHas(
            'emailEvidenceEvents',
            fn ($query) => $query->where('event_type', 'departure_auto_reply')
        )->count();
        $bounced = (clone $base)->whereNotNull('bounced_at')->count();
        $unsubscribed = (clone $base)->whereNotNull('unsubscribed_at')->count();

        $recipients = $this->applyFilters($this->recipientQuery())
            ->with([
                'campaign:id,name,status,launched_at',
                'congressionalOutreachDraftRecipient.profile.currentPosition.office',
                'events' => fn ($query) => $query->where('event_type', 'click')->orderByDesc('occurred_at'),
                'emailEvidenceEvents' => fn ($query) => $query
                    ->whereIn('event_type', ['human_reply', 'auto_reply', 'departure_auto_reply'])
                    ->with('gmailMessage:id,user_id,gmail_message_id,gmail_thread_id,subject,snippet,from_email,from_name,sent_at,labels')
                    ->orderByDesc('occurred_at'),
            ])
            ->latest('id')
            ->paginate(50);

        $recipientInsights = $recipients->getCollection()->mapWithKeys(function (OutreachCampaignRecipient $recipient): array {
            $humanReply = $recipient->emailEvidenceEvents->firstWhere('event_type', 'human_reply');
            $departure = $recipient->emailEvidenceEvents->firstWhere('event_type', 'departure_auto_reply');
            $autoReply = $recipient->emailEvidenceEvents->firstWhere('event_type', 'auto_reply');
            $response = $humanReply ?: $departure ?: $autoReply;
            $responseType = $humanReply ? 'human_reply' : ($departure ? 'departure_auto_reply' : ($autoReply ? 'auto_reply' : null));
            $clickGroups = $recipient->events
                ->filter(fn ($event) => filled($event->url))
                ->groupBy(fn ($event) => $this->normalizeUrl((string) $event->url))
                ->map(fn ($events, $url) => [
                    'url' => $url,
                    'requests' => $events->count(),
                    'last_at' => $events->max('occurred_at'),
                ])
                ->sortByDesc('requests')
                ->values();
            $knownScanner = $recipient->events->contains(function ($event): bool {
                $userAgent = (string) data_get($event->metadata, 'user_agent', '');

                return $userAgent !== '' && preg_match(
                    '/proofpoint|mimecast|barracuda|safelinks|urlscan|security|crawler|spider|\bbot\b|headless|curl|wget|python-requests/i',
                    $userAgent
                ) === 1;
            });
            $likelyScanner = $knownScanner || ($recipient->open_count === 0 && $recipient->events->count() >= 3);
            $gmailMessage = $response?->gmailMessage;

            return [$recipient->id => [
                'response_type' => $responseType,
                'response_label' => match ($responseType) {
                    'human_reply' => 'Human reply',
                    'departure_auto_reply' => 'Departure notice',
                    'auto_reply' => 'Auto-reply',
                    default => null,
                },
                'response_at' => $response?->occurred_at,
                'response_subject' => $gmailMessage?->subject,
                'response_snippet' => $gmailMessage?->snippet
                    ? html_entity_decode(strip_tags((string) $gmailMessage->snippet), ENT_QUOTES | ENT_HTML5)
                    : null,
                'gmail_url' => $this->gmailUrl($gmailMessage),
                'clicks' => $clickGroups,
                'click_requests' => $recipient->events->count(),
                'unique_destinations' => $clickGroups->count(),
                'click_classification' => $likelyScanner ? 'likely_scanner' : ($clickGroups->isNotEmpty() ? 'unverified' : null),
                'last_activity_at' => collect([
                    $recipient->sent_at,
                    $recipient->open_count > 0 ? $recipient->opened_at : null,
                    $recipient->clicked_at,
                    $response?->occurred_at,
                    $recipient->bounced_at,
                ])->filter()->sortDesc()->first(),
            ]];
        });

        $linkRows = OutreachRecipientEvent::query()
            ->where('event_type', 'click')
            ->whereHas('recipient.campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $this->draft->id))
            ->whereNotNull('url')
            ->selectRaw('url, campaign_recipient_id, COUNT(*) as clicks')
            ->groupBy('url', 'campaign_recipient_id')
            ->get();
        $topLinks = $linkRows
            ->groupBy(fn ($row) => $this->normalizeUrl((string) $row->url))
            ->map(fn ($rows, $url) => (object) [
                'url' => $url,
                'clicks' => (int) $rows->sum('clicks'),
                'unique_recipients' => $rows->pluck('campaign_recipient_id')->unique()->count(),
            ])
            ->sortByDesc('unique_recipients')
            ->take(10)
            ->values();

        $activity = $this->recipientQuery()
            ->whereNotNull('sent_at')
            ->get(['id', 'name', 'email', 'sent_at', 'opened_at', 'open_count', 'click_count', 'replied_at']);

        $humanReplyIds = $this->recipientQuery()
            ->whereHas('emailEvidenceEvents', fn ($query) => $query->where('event_type', 'human_reply'))
            ->pluck('id');

        $dailyActivity = collect(range(6, 0))->map(function (int $daysAgo) use ($activity): array {
            $day = now()->subDays($daysAgo);
            $items = $activity->filter(fn ($recipient) => $recipient->sent_at?->isSameDay($day));

            return [
                'label' => $day->format('D'),
                'sent' => $items->count(),
                'opened' => $items->where('open_count', '>', 0)->count(),
            ];
        });

        $mostEngaged = $activity
            ->filter(fn ($recipient) => $humanReplyIds->contains($recipient->id) || $recipient->open_count > 1)
            ->sortByDesc(fn ($recipient) => ($humanReplyIds->contains($recipient->id) ? 1000 : 0) + $recipient->open_count)
            ->take(5)
            ->map(function ($recipient) use ($humanReplyIds) {
                $recipient->setAttribute('has_human_reply', $humanReplyIds->contains($recipient->id));

                return $recipient;
            })
            ->values();

        $followUpCandidates = $activity
            ->filter(fn ($recipient) => $recipient->open_count >= 3 && ! $humanReplyIds->contains($recipient->id))
            ->count();

        return view('livewire.congressional-directory.outreach-analytics', [
            'recipients' => $recipients,
            'recipientInsights' => $recipientInsights,
            'campaigns' => $this->draft->outreachCampaigns()->latest('id')->get(),
            'topLinks' => $topLinks,
            'dailyActivity' => $dailyActivity,
            'mostEngaged' => $mostEngaged,
            'followUpCandidates' => $followUpCandidates,
            'metrics' => [
                'total' => (clone $base)->count(),
                'sent' => $sent,
                'opened' => $opened,
                'clicked' => $clicked,
                'replied' => $humanReplies,
                'human_replies' => $humanReplies,
                'auto_replies' => $autoReplies,
                'departure_notices' => $departureNotices,
                'bounced' => $bounced,
                'unsubscribed' => $unsubscribed,
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'suppressed' => (clone $base)->where('status', 'suppressed')->count(),
                'open_rate' => $this->rate($opened, $sent),
                'click_rate' => $this->rate($clicked, $sent),
                'click_through_rate' => $this->rate($clicked, $opened),
                'reply_rate' => $this->rate($humanReplies, $sent),
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
            'opened' => $query->where('open_count', '>', 0),
            'clicked' => $query->whereNotNull('clicked_at'),
            'replied' => $query->whereHas('emailEvidenceEvents', fn ($query) => $query->where('event_type', 'human_reply')),
            'auto_reply' => $query->whereHas('emailEvidenceEvents', fn ($query) => $query->whereIn('event_type', ['auto_reply', 'departure_auto_reply'])),
            'departure' => $query->whereHas('emailEvidenceEvents', fn ($query) => $query->where('event_type', 'departure_auto_reply')),
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

    protected function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || empty($parts['host'])) {
            return trim($url);
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '');
        $path = $path === '/' ? '' : $path;
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }

    protected function gmailUrl(?GmailMessage $message): ?string
    {
        if (! $message?->gmail_thread_id || $message->user_id !== Auth::id()) {
            return null;
        }

        return 'https://mail.google.com/mail/u/?authuser='.
            rawurlencode((string) Auth::user()?->email).
            '#all/'.rawurlencode((string) $message->gmail_thread_id);
    }
}
