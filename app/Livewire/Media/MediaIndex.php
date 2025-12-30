<?php

namespace App\Livewire\Media;

use App\Models\PressClip;
use App\Models\Pitch;
use App\Models\Inquiry;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MediaIndex extends Component
{
    use WithPagination;

    public string $activeTab = 'dashboard';

    // Coverage filters
    public string $search = '';
    public string $clipType = '';
    public string $sentiment = '';
    public string $dateRange = 'all';
    public string $clipStatus = 'approved';

    // Pitches state
    public string $pitchStatus = '';
    public string $pitchView = 'kanban';

    // Inquiries state
    public string $inquiryStatus = '';
    public string $inquiryUrgency = '';

    // Modals
    public bool $showClipModal = false;
    public bool $showPitchModal = false;
    public bool $showInquiryModal = false;
    public ?int $editingId = null;

    // Clip form
    public array $clipForm = [];

    // Pitch form
    public array $pitchForm = [];

    // Inquiry form
    public array $inquiryForm = [];

    protected $queryString = [
        'activeTab' => ['except' => 'dashboard'],
        'search' => ['except' => ''],
        'dateRange' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->resetClipForm();
        $this->resetPitchForm();
        $this->resetInquiryForm();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ===== Stats Properties =====

    public function getStatsProperty(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $thisQuarter = now()->startOfQuarter();
        $lastQuarter = now()->subQuarter()->startOfQuarter();
        $lastQuarterEnd = now()->subQuarter()->endOfQuarter();

        // Clips counts
        $clipsThisMonth = PressClip::approved()->where('published_at', '>=', $thisMonth)->count();
        $clipsLastMonth = PressClip::approved()
            ->whereBetween('published_at', [$lastMonth, $lastMonthEnd])
            ->count();

        $clipsThisQuarter = PressClip::approved()->where('published_at', '>=', $thisQuarter)->count();
        $clipsLastQuarter = PressClip::approved()
            ->whereBetween('published_at', [$lastQuarter, $lastQuarterEnd])
            ->count();

        // Staff quoted (clips with staff mentioned + unique count)
        $staffQuotedClips = PressClip::approved()
            ->where('published_at', '>=', $thisQuarter)
            ->whereHas('staffMentioned')
            ->with('staffMentioned')
            ->get();

        $staffQuotedCount = $staffQuotedClips->count();
        $uniqueStaffQuoted = $staffQuotedClips->flatMap->staffMentioned->unique('id')->count();

        // Sentiment breakdown
        $sentimentCounts = PressClip::approved()
            ->where('published_at', '>=', $thisQuarter)
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        // Type breakdown
        $typeCounts = PressClip::approved()
            ->where('published_at', '>=', $thisQuarter)
            ->selectRaw('clip_type, COUNT(*) as count')
            ->groupBy('clip_type')
            ->orderByDesc('count')
            ->pluck('count', 'clip_type')
            ->toArray();

        // Top outlets
        $topOutlets = PressClip::approved()
            ->where('published_at', '>=', $thisQuarter)
            ->selectRaw('outlet_name, COUNT(*) as count')
            ->groupBy('outlet_name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'outlet_name')
            ->toArray();

        // Calculate positive rate
        $total = array_sum($sentimentCounts);
        $positive = $sentimentCounts['positive'] ?? 0;
        $positiveRate = $total > 0 ? round(($positive / $total) * 100) : 0;

        // Calculate month/quarter changes
        $monthChange = $clipsLastMonth > 0
            ? round((($clipsThisMonth - $clipsLastMonth) / $clipsLastMonth) * 100)
            : ($clipsThisMonth > 0 ? 100 : 0);
        $quarterChange = $clipsLastQuarter > 0
            ? round((($clipsThisQuarter - $clipsLastQuarter) / $clipsLastQuarter) * 100)
            : ($clipsThisQuarter > 0 ? 100 : 0);

        // Outlets count
        $outletsCount = Organization::where('type', 'media')->count();

        return [
            'clips_this_month' => $clipsThisMonth,
            'clips_this_quarter' => $clipsThisQuarter,
            'clips_month_change' => $monthChange,
            'clips_quarter_change' => $quarterChange,
            'staff_quoted' => $staffQuotedCount,
            'unique_staff_quoted' => $uniqueStaffQuoted,
            'positive_rate' => $positiveRate,
            'sentiment' => $sentimentCounts,
            'types' => $typeCounts,
            'top_outlets' => $topOutlets,
            'outlets_count' => $outletsCount,
            'pending_review' => PressClip::pendingReview()->count(),
            'total_clips_year' => PressClip::approved()->thisYear()->count(),
        ];
    }

    public function getNeedsAttentionProperty(): array
    {
        // Urgent inquiries (deadline today or overdue, or by status)
        $urgentInquiries = Inquiry::with(['journalist', 'outlet'])
            ->whereIn('status', ['new', 'responding'])
            ->where(function ($q) {
                $q->where('deadline', '<=', now()->addDay())
                    ->orWhereIn('urgency', ['urgent', 'breaking']);
            })
            ->orderBy('deadline')
            ->get();

        // All open inquiries count
        $openInquiriesCount = Inquiry::whereIn('status', ['new', 'responding'])->count();

        // Pitches awaiting response 7+ days
        $pitchesAwaiting = Pitch::with(['journalist', 'outlet'])
            ->whereIn('status', ['sent', 'following_up'])
            ->where('pitched_at', '<=', now()->subDays(7))
            ->orderBy('pitched_at')
            ->get();

        // Clips pending review
        $clipsPendingCount = PressClip::where('status', 'pending_review')->count();

        // Generate suggestion
        $suggestion = $this->generateSuggestion();

        return [
            'inquiries_urgent' => $urgentInquiries,
            'inquiries_open_count' => $openInquiriesCount,
            'pitches_awaiting' => $pitchesAwaiting,
            'clips_pending_count' => $clipsPendingCount,
            'suggestion' => $suggestion,
        ];
    }

    protected function generateSuggestion(): ?array
    {
        // Check days since last pitch
        $lastPitch = Pitch::whereNotNull('pitched_at')
            ->latest('pitched_at')
            ->first();

        $daysSinceLastPitch = $lastPitch
            ? $lastPitch->pitched_at->diffInDays(now())
            : null;

        if ($daysSinceLastPitch === null || $daysSinceLastPitch > 30) {
            // Suggest a journalist to pitch (one with clips, high engagement)
            $suggestedJournalist = Person::whereHas('organization', fn($q) => $q->where('type', 'media'))
                ->withCount('pressClips')
                ->orderByDesc('press_clips_count')
                ->first();

            if ($suggestedJournalist) {
                return [
                    'type' => 'pitch_reminder',
                    'days' => $daysSinceLastPitch,
                    'journalist' => $suggestedJournalist,
                ];
            }

            return [
                'type' => 'pitch_reminder',
                'days' => $daysSinceLastPitch,
                'journalist' => null,
            ];
        }

        // Check for good quarter momentum
        $clipsThisQuarter = PressClip::approved()
            ->where('published_at', '>=', now()->startOfQuarter())
            ->count();

        $clipsLastQuarter = PressClip::approved()
            ->whereBetween('published_at', [
                now()->subQuarter()->startOfQuarter(),
                now()->subQuarter()->endOfQuarter()
            ])
            ->count();

        if ($clipsThisQuarter > $clipsLastQuarter && $clipsThisQuarter >= 3) {
            return [
                'type' => 'good_quarter',
                'count' => $clipsThisQuarter,
                'vs_last' => $clipsLastQuarter,
            ];
        }

        return null;
    }

    protected function calculateSentimentRate(string $sentiment): int
    {
        $total = PressClip::approved()->thisQuarter()->count();
        if ($total === 0)
            return 0;

        $positive = PressClip::approved()->thisQuarter()->where('sentiment', $sentiment)->count();
        return round(($positive / $total) * 100);
    }

    // ===== Coverage (Clips) =====

    public function getRecentClipsProperty()
    {
        return PressClip::approved()
            ->with(['journalist', 'outlet', 'staffMentioned'])
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    public function getClipsProperty()
    {
        $query = PressClip::with(['journalist', 'outlet', 'issues', 'projects', 'staffMentioned']);

        if ($this->clipStatus === 'pending_review') {
            $query->pendingReview();
        } else {
            $query->approved();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('summary', 'like', "%{$this->search}%")
                    ->orWhere('outlet_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->clipType) {
            $query->where('clip_type', $this->clipType);
        }

        if ($this->sentiment) {
            $query->where('sentiment', $this->sentiment);
        }

        $query->when($this->dateRange === 'week', fn($q) => $q->where('published_at', '>=', now()->subWeek()))
            ->when($this->dateRange === 'month', fn($q) => $q->where('published_at', '>=', now()->subMonth()))
            ->when($this->dateRange === 'quarter', fn($q) => $q->where('published_at', '>=', now()->subQuarter()))
            ->when($this->dateRange === 'year', fn($q) => $q->where('published_at', '>=', now()->subYear()));

        return $query->latest('published_at')->paginate(12);
    }

    // ===== Pitches =====

    public function getPitchesProperty()
    {
        $query = Pitch::with(['journalist', 'outlet', 'project', 'pitchedBy', 'issues']);

        if ($this->search && $this->activeTab === 'pitches') {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->pitchStatus) {
            $query->where('status', $this->pitchStatus);
        }

        return $query->latest('updated_at')->get();
    }

    public function getPitchesByStatusProperty(): array
    {
        $pitches = $this->pitches;

        return [
            'draft' => $pitches->where('status', 'draft'),
            'sent' => $pitches->where('status', 'sent'),
            'following_up' => $pitches->where('status', 'following_up'),
            'accepted' => $pitches->where('status', 'accepted'),
            'published' => $pitches->where('status', 'published'),
            'closed' => $pitches->whereIn('status', ['declined', 'no_response']),
        ];
    }

    public function getPitchStatsProperty(): array
    {
        $last90Days = now()->subDays(90);
        $sent = Pitch::where('pitched_at', '>=', $last90Days)->count();
        $successful = Pitch::where('pitched_at', '>=', $last90Days)
            ->whereIn('status', ['accepted', 'published'])
            ->count();

        return [
            'sent' => $sent,
            'successful' => $successful,
            'success_rate' => $sent > 0 ? round(($successful / $sent) * 100) : 0,
        ];
    }

    // ===== Inquiries =====

    public function getInquiriesProperty()
    {
        $query = Inquiry::with(['journalist', 'outlet', 'handledBy', 'issues', 'resultingClip']);

        if ($this->search && $this->activeTab === 'inquiries') {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->inquiryStatus) {
            $query->where('status', $this->inquiryStatus);
        }

        if ($this->inquiryUrgency) {
            $query->where('urgency', $this->inquiryUrgency);
        }

        return $query->latest('received_at')->get();
    }

    public function getGroupedInquiriesProperty(): array
    {
        $inquiries = $this->inquiries;

        return [
            'urgent' => $inquiries->filter(
                fn($i) =>
                $i->is_overdue ||
                ($i->deadline && $i->deadline->isToday()) ||
                (in_array($i->urgency, ['urgent', 'breaking']) && $i->status !== 'completed')
            ),
            'new' => $inquiries->where('status', 'new')->filter(
                fn($i) =>
                !$i->is_overdue &&
                !($i->deadline && $i->deadline->isToday()) &&
                !in_array($i->urgency, ['urgent', 'breaking'])
            ),
            'responding' => $inquiries->where('status', 'responding')->filter(
                fn($i) =>
                !$i->is_overdue &&
                !($i->deadline && $i->deadline->isToday()) &&
                !in_array($i->urgency, ['urgent', 'breaking'])
            ),
            'completed' => $inquiries->where('status', 'completed')->take(10),
        ];
    }

    // ===== Press Contacts =====

    public function getJournalistsProperty()
    {
        return Person::journalists()
            ->with(['organization'])
            ->withCount(['pressClips', 'pitchesReceived', 'inquiriesMade'])
            ->orderByDesc('press_clips_count')
            ->limit(20)
            ->get();
    }

    public function getRecentContactsProperty()
    {
        return Person::journalists()
            ->with('organization')
            ->latest('updated_at')
            ->limit(3)
            ->get();
    }

    // ===== Lookups =====

    public function getOutletsProperty()
    {
        return Organization::where('type', 'media')
            ->withCount(['pressClips', 'pitches', 'inquiries'])
            ->orderBy('name')
            ->get();
    }

    public function getMediaOutletsProperty()
    {
        return Organization::where('type', 'media')
            ->withCount(['pressClips', 'pitches', 'inquiries'])
            ->with(['journalists' => fn($q) => $q->limit(5)])
            ->when($this->search && $this->activeTab === 'outlets', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();
    }

    public function getIssuesProperty()
    {
        return Issue::orderBy('name')->get();
    }

    public function getProjectsProperty()
    {
        return Project::orderBy('name')->get();
    }

    public function getTeamMembersProperty()
    {
        return User::orderBy('name')->get();
    }

    // ===== Clip Actions =====

    public function openClipModal(?int $id = null): void
    {
        if ($id) {
            $clip = PressClip::find($id);
            $this->editingId = $id;
            $this->clipForm = [
                'title' => $clip->title,
                'url' => $clip->url,
                'outlet_name' => $clip->outlet_name,
                'outlet_id' => $clip->outlet_id,
                'journalist_id' => $clip->journalist_id,
                'journalist_name' => $clip->journalist_name,
                'published_at' => $clip->published_at?->format('Y-m-d'),
                'clip_type' => $clip->clip_type,
                'sentiment' => $clip->sentiment,
                'summary' => $clip->summary,
                'quotes' => $clip->quotes,
                'notes' => $clip->notes,
                'image_url' => $clip->image_url ?? '',
                'raw_text' => '',
                'issue_ids' => $clip->issues->pluck('id')->toArray(),
                'project_ids' => $clip->projects->pluck('id')->toArray(),
                'staff_ids' => $clip->staffMentioned->pluck('id')->toArray(),
            ];
        } else {
            $this->editingId = null;
            $this->resetClipForm();
        }
        $this->showClipModal = true;
    }

    /**
     * Edit an existing clip - alias for openClipModal.
     */
    public function editClip(int $id): void
    {
        $this->openClipModal($id);
    }

    public function fetchClipFromUrl(): void
    {
        $url = $this->clipForm['url'] ?? '';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->dispatch('notify', type: 'error', message: 'Please enter a valid URL first.');
            return;
        }

        $this->dispatch('notify', type: 'info', message: 'Fetching article with AI...');

        try {
            // Fetch the page content
            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                throw new \Exception('Could not fetch URL');
            }

            $html = $response->body();

            // Extract og:image for article thumbnail
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
                $this->clipForm['image_url'] = $m[1];
            }

            // Strip HTML to get text content (limit to first 8000 chars for AI)
            $textContent = strip_tags(preg_replace('/<script[^>]*>.*?<\/script>/s', '', $html));
            $textContent = preg_replace('/\s+/', ' ', $textContent);
            $textContent = substr(trim($textContent), 0, 8000);

            // Get team members list for AI to match against
            $teamMembers = User::orderBy('name')->pluck('name')->toArray();
            $teamMembersStr = implode(', ', $teamMembers);

            // Use AI to extract article details
            $prompt = <<<PROMPT
Extract article information from the following text. The article URL is: {$url}

Team members to look for (if mentioned): {$teamMembersStr}

ARTICLE TEXT:
{$textContent}

Return ONLY valid JSON with these fields:
{
  "headline": "article title/headline",
  "outlet_name": "news outlet or publication name",
  "journalist_name": "author/reporter name if found",
  "published_date": "YYYY-MM-DD format if found, otherwise null",
  "summary": "brief 1-2 sentence summary of article",
  "sentiment": "positive, neutral, negative, or mixed regarding POPVOX Foundation",
  "staff_mentioned": ["array of team member names that appear in the article"],
  "quotes": "any direct quotes from POPVOX staff if found"
}
PROMPT;

            $aiResponse = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 1000,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);

            if ($aiResponse->successful()) {
                $content = $aiResponse->json('content.0.text');

                // Parse JSON from response
                if (preg_match('/\{[^{}]*\}/s', $content, $matches)) {
                    $data = json_decode($matches[0], true);

                    if ($data) {
                        if (!empty($data['headline'])) {
                            $this->clipForm['title'] = $data['headline'];
                        }
                        if (!empty($data['outlet_name'])) {
                            $this->clipForm['outlet_name'] = $data['outlet_name'];
                        }
                        if (!empty($data['journalist_name'])) {
                            $this->clipForm['journalist_name'] = $data['journalist_name'];
                        }
                        if (!empty($data['published_date'])) {
                            $this->clipForm['published_at'] = $data['published_date'];
                        }
                        if (!empty($data['summary'])) {
                            $this->clipForm['summary'] = $data['summary'];
                        }
                        if (!empty($data['sentiment'])) {
                            $this->clipForm['sentiment'] = $data['sentiment'];
                        }
                        if (!empty($data['quotes'])) {
                            $this->clipForm['quotes'] = $data['quotes'];
                        }

                        // Match staff mentioned to user IDs
                        if (!empty($data['staff_mentioned']) && is_array($data['staff_mentioned'])) {
                            $staffIds = [];
                            foreach ($data['staff_mentioned'] as $staffName) {
                                $user = User::where('name', 'like', '%' . $staffName . '%')->first();
                                if ($user) {
                                    $staffIds[] = $user->id;
                                }
                            }
                            if (!empty($staffIds)) {
                                $this->clipForm['staff_ids'] = $staffIds;
                            }
                        }

                        $this->dispatch('notify', type: 'success', message: 'Article analyzed! Review and save.');
                        return;
                    }
                }
            }

            // Fallback to basic HTML extraction if AI fails
            $this->extractBasicMetadata($html, $url);
            $this->dispatch('notify', type: 'warning', message: 'Basic extraction complete. Some fields may need manual entry.');

        } catch (\Exception $e) {
            Log::error('Clip URL fetch error: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Could not fetch article details. Please fill in manually.');
        }
    }

    protected function extractBasicMetadata(string $html, string $url): void
    {
        // Try og:title first, then regular title
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            $this->clipForm['title'] = html_entity_decode($m[1]);
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/', $html, $m)) {
            $this->clipForm['title'] = html_entity_decode(trim($m[1]));
        }

        // og:site_name for outlet
        if (preg_match('/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            $this->clipForm['outlet_name'] = html_entity_decode($m[1]);
        } else {
            $host = parse_url($url, PHP_URL_HOST);
            $this->clipForm['outlet_name'] = ucfirst(explode('.', preg_replace('/^www\./', '', $host))[0]);
        }

        // Try to find author
        if (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            $this->clipForm['journalist_name'] = html_entity_decode($m[1]);
        }

        // Try to find publication date
        if (preg_match('/<meta[^>]+property=["\']article:published_time["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            try {
                $this->clipForm['published_at'] = \Carbon\Carbon::parse($m[1])->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        // og:description for summary
        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            $this->clipForm['summary'] = html_entity_decode($m[1]);
        }
    }

    public function extractFromPastedText(): void
    {
        $rawText = $this->clipForm['raw_text'] ?? '';

        if (empty(trim($rawText))) {
            $this->dispatch('notify', type: 'error', message: 'Please paste article text first.');
            return;
        }

        $this->dispatch('notify', type: 'info', message: 'Analyzing article text...');

        try {
            // Limit text to 10000 chars for AI
            $textContent = substr(trim($rawText), 0, 10000);

            // Get team members list for AI to match against
            $teamMembers = User::orderBy('name')->pluck('name')->toArray();
            $teamMembersStr = implode(', ', $teamMembers);

            $prompt = <<<PROMPT
Analyze the following article text and extract information.

Team members to look for (if mentioned): {$teamMembersStr}

ARTICLE TEXT:
{$textContent}

Return ONLY valid JSON with these fields:
{
  "headline": "article title/headline if identifiable",
  "outlet_name": "news outlet or publication name if identifiable",
  "journalist_name": "author/reporter name if found",
  "summary": "brief 1-2 sentence summary of article",
  "sentiment": "positive, neutral, negative, or mixed regarding POPVOX Foundation",
  "staff_mentioned": ["array of team member names that appear in the article"],
  "quotes": "any direct quotes from POPVOX staff, separated by semicolons if multiple"
}
PROMPT;

            $aiResponse = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 1000,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);

            if ($aiResponse->successful()) {
                $content = $aiResponse->json('content.0.text');

                // Parse JSON from response
                if (preg_match('/\{[^{}]*\}/s', $content, $matches)) {
                    $data = json_decode($matches[0], true);

                    if ($data) {
                        // Only update fields if they're empty or use new data
                        if (!empty($data['headline']) && empty($this->clipForm['title'])) {
                            $this->clipForm['title'] = $data['headline'];
                        }
                        if (!empty($data['outlet_name']) && empty($this->clipForm['outlet_name'])) {
                            $this->clipForm['outlet_name'] = $data['outlet_name'];
                        }
                        if (!empty($data['journalist_name']) && empty($this->clipForm['journalist_name'])) {
                            $this->clipForm['journalist_name'] = $data['journalist_name'];
                        }
                        if (!empty($data['summary'])) {
                            $this->clipForm['summary'] = $data['summary'];
                        }
                        if (!empty($data['sentiment'])) {
                            $this->clipForm['sentiment'] = $data['sentiment'];
                        }
                        if (!empty($data['quotes'])) {
                            $this->clipForm['quotes'] = $data['quotes'];
                        }

                        // Match staff mentioned to user IDs
                        if (!empty($data['staff_mentioned']) && is_array($data['staff_mentioned'])) {
                            $staffIds = [];
                            foreach ($data['staff_mentioned'] as $staffName) {
                                $user = User::where('name', 'like', '%' . $staffName . '%')->first();
                                if ($user) {
                                    $staffIds[] = $user->id;
                                }
                            }
                            if (!empty($staffIds)) {
                                $this->clipForm['staff_ids'] = $staffIds;
                            }
                        }

                        $staffCount = count($this->clipForm['staff_ids'] ?? []);
                        $message = 'Text analyzed!';
                        if ($staffCount > 0) {
                            $message .= " Found {$staffCount} staff mention(s).";
                        }
                        $this->dispatch('notify', type: 'success', message: $message);
                        return;
                    }
                }
            }

            $this->dispatch('notify', type: 'warning', message: 'Could not extract information from text.');

        } catch (\Exception $e) {
            Log::error('Text extraction error: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Error analyzing text. Please try again.');
        }
    }

    public function saveClip(): void
    {
        $this->validate([
            'clipForm.title' => 'required|string|max:255',
            'clipForm.url' => 'required|url',
            'clipForm.outlet_name' => 'required|string|max:255',
            'clipForm.published_at' => 'required|date',
        ]);

        // Auto-create or find outlet organization
        $outletId = $this->clipForm['outlet_id'];
        if (!$outletId && $this->clipForm['outlet_name']) {
            $outletId = $this->findOrCreateOutlet($this->clipForm['outlet_name']);
        }

        // Auto-create or find journalist as Person record
        $journalistId = $this->clipForm['journalist_id'];
        if (!$journalistId && $this->clipForm['journalist_name']) {
            $journalistId = $this->findOrCreateJournalist(
                $this->clipForm['journalist_name'],
                null, // email not provided from clip
                $this->clipForm['outlet_name'],
                $outletId
            );
        }

        $data = [
            'title' => $this->clipForm['title'],
            'url' => $this->clipForm['url'],
            'outlet_name' => $this->clipForm['outlet_name'],
            'outlet_id' => $outletId,
            'journalist_id' => $journalistId,
            'journalist_name' => $this->clipForm['journalist_name'],
            'published_at' => $this->clipForm['published_at'],
            'clip_type' => $this->clipForm['clip_type'],
            'sentiment' => $this->clipForm['sentiment'],
            'summary' => $this->clipForm['summary'],
            'quotes' => $this->clipForm['quotes'],
            'notes' => $this->clipForm['notes'],
            'image_url' => $this->clipForm['image_url'] ?: null,
            'status' => 'approved',
            'source' => 'manual',
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $clip = PressClip::find($this->editingId);
            $clip->update($data);
        } else {
            $clip = PressClip::create($data);
        }

        $clip->issues()->sync($this->clipForm['issue_ids'] ?? []);
        $clip->projects()->sync($this->clipForm['project_ids'] ?? []);
        $clip->staffMentioned()->sync($this->clipForm['staff_ids'] ?? []);

        $this->showClipModal = false;
        $this->dispatch('notify', type: 'success', message: 'Press clip saved!');
    }

    public function approveClip(int $id): void
    {
        PressClip::find($id)?->update(['status' => 'approved']);
        $this->dispatch('notify', type: 'success', message: 'Clip approved!');
    }

    public function rejectClip(int $id): void
    {
        PressClip::find($id)?->update(['status' => 'rejected']);
    }

    protected function resetClipForm(): void
    {
        $this->clipForm = [
            'title' => '',
            'url' => '',
            'outlet_name' => '',
            'outlet_id' => null,
            'journalist_id' => null,
            'journalist_name' => '',
            'published_at' => now()->format('Y-m-d'),
            'clip_type' => 'article',
            'sentiment' => 'neutral',
            'summary' => '',
            'quotes' => '',
            'notes' => '',
            'image_url' => '',
            'issue_ids' => [],
            'project_ids' => [],
            'staff_ids' => [],
            'raw_text' => '',
        ];
    }

    // ===== Pitch Actions =====

    public function openPitchModal(?int $id = null): void
    {
        if ($id) {
            $pitch = Pitch::find($id);
            $this->editingId = $id;
            $this->pitchForm = [
                'subject' => $pitch->subject,
                'description' => $pitch->description,
                'status' => $pitch->status,
                'journalist_id' => $pitch->journalist_id,
                'journalist_name' => $pitch->journalist_name,
                'journalist_email' => $pitch->journalist_email,
                'outlet_id' => $pitch->outlet_id,
                'outlet_name' => $pitch->outlet_name,
                'project_id' => $pitch->project_id,
                'issue_ids' => $pitch->issues->pluck('id')->toArray(),
                'notes' => $pitch->notes,
            ];
        } else {
            $this->editingId = null;
            $this->resetPitchForm();
        }
        $this->showPitchModal = true;
    }

    public function savePitch(): void
    {
        $this->validate([
            'pitchForm.subject' => 'required|string|max:255',
            'pitchForm.description' => 'required|string',
        ]);

        // Auto-create or link journalist as Person with is_journalist flag
        $journalistId = $this->pitchForm['journalist_id'];
        if (!$journalistId && $this->pitchForm['journalist_name']) {
            $journalistId = $this->findOrCreateJournalist(
                $this->pitchForm['journalist_name'],
                $this->pitchForm['journalist_email'],
                $this->pitchForm['outlet_name'],
                $this->pitchForm['outlet_id']
            );
        }

        $data = [
            'subject' => $this->pitchForm['subject'],
            'description' => $this->pitchForm['description'],
            'status' => $this->pitchForm['status'],
            'journalist_id' => $journalistId,
            'journalist_name' => $this->pitchForm['journalist_name'],
            'journalist_email' => $this->pitchForm['journalist_email'],
            'outlet_id' => $this->pitchForm['outlet_id'] ?: null,
            'outlet_name' => $this->pitchForm['outlet_name'],
            'project_id' => $this->pitchForm['project_id'] ?: null,
            'notes' => $this->pitchForm['notes'],
            'pitched_by' => auth()->id(),
        ];

        if ($this->pitchForm['status'] === 'sent' && !$this->editingId) {
            $data['pitched_at'] = now();
        }

        if ($this->editingId) {
            $pitch = Pitch::find($this->editingId);
            if ($pitch->status === 'draft' && $this->pitchForm['status'] === 'sent') {
                $data['pitched_at'] = now();
            }
            $pitch->update($data);
        } else {
            $pitch = Pitch::create($data);
        }

        $pitch->issues()->sync($this->pitchForm['issue_ids'] ?? []);

        $this->showPitchModal = false;
        $this->dispatch('notify', type: 'success', message: 'Pitch saved!');
    }

    public function updatePitchStatus(int $id, string $status): void
    {
        $pitch = Pitch::find($id);
        $data = ['status' => $status];

        if ($status === 'sent' && !$pitch->pitched_at) {
            $data['pitched_at'] = now();
        }

        $pitch->update($data);
    }

    protected function resetPitchForm(): void
    {
        $this->pitchForm = [
            'subject' => '',
            'description' => '',
            'status' => 'draft',
            'journalist_id' => null,
            'journalist_name' => '',
            'journalist_email' => '',
            'outlet_id' => null,
            'outlet_name' => '',
            'project_id' => null,
            'issue_ids' => [],
            'notes' => '',
        ];
    }

    // ===== Inquiry Actions =====

    public function openInquiryModal(?int $id = null): void
    {
        if ($id) {
            $inquiry = Inquiry::find($id);
            $this->editingId = $id;
            $this->inquiryForm = [
                'subject' => $inquiry->subject,
                'description' => $inquiry->description,
                'status' => $inquiry->status,
                'urgency' => $inquiry->urgency,
                'received_at' => $inquiry->received_at->format('Y-m-d\TH:i'),
                'deadline' => $inquiry->deadline?->format('Y-m-d\TH:i'),
                'journalist_id' => $inquiry->journalist_id,
                'journalist_name' => $inquiry->journalist_name,
                'journalist_email' => $inquiry->journalist_email,
                'outlet_id' => $inquiry->outlet_id,
                'outlet_name' => $inquiry->outlet_name,
                'project_id' => $inquiry->project_id,
                'handled_by' => $inquiry->handled_by,
                'issue_ids' => $inquiry->issues->pluck('id')->toArray(),
                'response_notes' => $inquiry->response_notes,
            ];
        } else {
            $this->editingId = null;
            $this->resetInquiryForm();
        }
        $this->showInquiryModal = true;
    }

    public function saveInquiry(): void
    {
        $this->validate([
            'inquiryForm.subject' => 'required|string|max:255',
            'inquiryForm.description' => 'required|string',
            'inquiryForm.received_at' => 'required|date',
        ]);

        // Auto-create or link journalist as Person with is_journalist flag
        $journalistId = $this->inquiryForm['journalist_id'];
        if (!$journalistId && $this->inquiryForm['journalist_name']) {
            $journalistId = $this->findOrCreateJournalist(
                $this->inquiryForm['journalist_name'],
                $this->inquiryForm['journalist_email'],
                $this->inquiryForm['outlet_name'],
                $this->inquiryForm['outlet_id']
            );
        }

        $data = [
            'subject' => $this->inquiryForm['subject'],
            'description' => $this->inquiryForm['description'],
            'status' => $this->inquiryForm['status'],
            'urgency' => $this->inquiryForm['urgency'],
            'received_at' => $this->inquiryForm['received_at'],
            'deadline' => $this->inquiryForm['deadline'] ?: null,
            'journalist_id' => $journalistId,
            'journalist_name' => $this->inquiryForm['journalist_name'],
            'journalist_email' => $this->inquiryForm['journalist_email'],
            'outlet_id' => $this->inquiryForm['outlet_id'] ?: null,
            'outlet_name' => $this->inquiryForm['outlet_name'],
            'project_id' => $this->inquiryForm['project_id'] ?: null,
            'handled_by' => $this->inquiryForm['handled_by'] ?: null,
            'response_notes' => $this->inquiryForm['response_notes'],
        ];

        if ($this->editingId) {
            $inquiry = Inquiry::find($this->editingId);
            $inquiry->update($data);
        } else {
            $data['created_by'] = auth()->id();
            $inquiry = Inquiry::create($data);
        }

        $inquiry->issues()->sync($this->inquiryForm['issue_ids'] ?? []);

        $this->showInquiryModal = false;
        $this->dispatch('notify', type: 'success', message: 'Inquiry saved!');
    }

    /**
     * Find existing journalist or create new Person with is_journalist flag.
     * Also creates Organization from outlet name if needed.
     */
    protected function findOrCreateJournalist(string $name, ?string $email, ?string $outletName, ?int $outletId): int
    {
        // First try to find by email if provided
        if ($email) {
            $existing = Person::where('email', $email)->first();
            if ($existing) {
                // Ensure they're marked as journalist
                if (!$existing->is_journalist) {
                    $existing->update(['is_journalist' => true]);
                }
                // Update org if we have outlet info and person doesn't have one
                if (!$existing->organization_id && ($outletId || $outletName)) {
                    $orgId = $outletId ?? $this->findOrCreateOutlet($outletName);
                    $existing->update(['organization_id' => $orgId]);
                }
                return $existing->id;
            }
        }

        // Resolve outlet ID - create org if only name is provided
        $resolvedOutletId = $outletId;
        if (!$resolvedOutletId && $outletName) {
            $resolvedOutletId = $this->findOrCreateOutlet($outletName);
        }

        // Try to find by name + outlet
        $query = Person::where('name', 'like', $name);
        if ($resolvedOutletId) {
            $query->where('organization_id', $resolvedOutletId);
        }
        $existing = $query->first();

        if ($existing) {
            $updates = [];
            if (!$existing->is_journalist) {
                $updates['is_journalist'] = true;
            }
            if (!$existing->organization_id && $resolvedOutletId) {
                $updates['organization_id'] = $resolvedOutletId;
            }
            if (!empty($updates)) {
                $existing->update($updates);
            }
            return $existing->id;
        }

        // Create new person as journalist
        $person = Person::create([
            'name' => $name,
            'email' => $email,
            'organization_id' => $resolvedOutletId,
            'is_journalist' => true,
            'status' => 'active',
            'source' => 'media_inquiry',
        ]);

        return $person->id;
    }

    /**
     * Find existing organization by name or create new one as media outlet.
     */
    protected function findOrCreateOutlet(string $name): int
    {
        // Try to find existing by name (case-insensitive)
        $existing = Organization::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();

        if ($existing) {
            return $existing->id;
        }

        // Create new organization as media outlet
        $org = Organization::create([
            'name' => trim($name),
            'type' => 'media',
            'status' => 'active',
        ]);

        return $org->id;
    }

    /**
     * Use AI to extract insights from an inquiry.
     */
    public function analyzeInquiryWithAI(int $id): void
    {
        $inquiry = Inquiry::find($id);
        if (!$inquiry)
            return;

        $this->dispatch('notify', type: 'info', message: 'Analyzing inquiry with AI...');

        try {
            $prompt = $this->buildInquiryAnalysisPrompt($inquiry);

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 1500,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text');
                $insights = $this->parseInquiryInsights($content);

                $inquiry->update(['ai_insights' => $insights]);

                $this->dispatch('notify', type: 'success', message: 'AI analysis complete!');
            } else {
                throw new \Exception('API request failed');
            }
        } catch (\Exception $e) {
            \Log::error('Inquiry AI analysis failed: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'AI analysis failed. Please try again.');
        }
    }

    protected function buildInquiryAnalysisPrompt(Inquiry $inquiry): string
    {
        return "Analyze this media inquiry and extract insights. Return a JSON object with the following structure:

{
  \"summary\": \"Brief 1-2 sentence summary of what the journalist is asking about\",
  \"key_topics\": [\"list\", \"of\", \"main\", \"topics\"],
  \"suggested_angle\": \"Recommended approach/angle for responding\",
  \"talking_points\": [\"Key point 1\", \"Key point 2\", \"Key point 3\"],
  \"potential_concerns\": [\"Any concerns or sensitive topics to be aware of\"],
  \"related_work\": \"Any POPVOX projects or initiatives that relate to this inquiry\",
  \"urgency_assessment\": \"Your assessment of how urgent this actually is and why\",
  \"recommended_responder\": \"Type of person best suited to respond (e.g., ED, policy expert, comms lead)\"
}

INQUIRY DETAILS:
Subject: {$inquiry->subject}
From: {$inquiry->journalist_display_name} at {$inquiry->outlet_display_name}
Urgency: {$inquiry->urgency}
Deadline: " . ($inquiry->deadline ? $inquiry->deadline->format('M j, Y g:i A') : 'Not specified') . "

Description:
{$inquiry->description}

Return ONLY the JSON object, no other text.";
    }

    protected function parseInquiryInsights(string $content): array
    {
        // Try to extract JSON from the response
        $content = trim($content);

        // Remove markdown code blocks if present
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
        }

        try {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $parsed;
        } catch (\JsonException $e) {
            // If JSON parsing fails, return raw content as summary
            return [
                'summary' => $content,
                'key_topics' => [],
                'talking_points' => [],
                'parse_error' => true,
            ];
        }
    }

    public function assignInquiryToMe(int $id): void
    {
        Inquiry::find($id)?->assignTo(auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Inquiry assigned to you!');
    }

    public function updateInquiryStatus(int $id, string $status): void
    {
        Inquiry::find($id)?->update(['status' => $status]);
    }

    protected function resetInquiryForm(): void
    {
        $this->inquiryForm = [
            'subject' => '',
            'description' => '',
            'status' => 'new',
            'urgency' => 'standard',
            'received_at' => now()->format('Y-m-d\TH:i'),
            'deadline' => '',
            'journalist_id' => null,
            'journalist_name' => '',
            'journalist_email' => '',
            'outlet_id' => null,
            'outlet_name' => '',
            'project_id' => null,
            'handled_by' => null,
            'issue_ids' => [],
            'response_notes' => '',
        ];
    }


    public function closeModal(): void
    {
        $this->showClipModal = false;
        $this->showPitchModal = false;
        $this->showInquiryModal = false;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.media.media-index', [
            'stats' => $this->stats,
            'needsAttention' => $this->needsAttention,
            'recentClips' => $this->recentClips,
            'recentContacts' => $this->recentContacts,
            'clips' => $this->clips,
            'pitches' => $this->pitches,
            'pitchesByStatus' => $this->pitchesByStatus,
            'pitchStats' => $this->pitchStats,
            'inquiries' => $this->inquiries,
            'groupedInquiries' => $this->groupedInquiries,
            'journalists' => $this->journalists,
            'outlets' => $this->outlets,
            'mediaOutlets' => $this->mediaOutlets,
            'issues' => $this->issues,
            'projects' => $this->projects,
            'teamMembers' => $this->teamMembers,
        ]);
    }
}
