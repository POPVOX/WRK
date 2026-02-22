<?php

namespace App\Livewire\Communications;

use App\Models\Action;
use App\Models\GmailMessage;
use App\Models\Person;
use App\Models\PersonInteraction;
use App\Models\Project;
use App\Services\GoogleGmailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Inbox')]
class InboxIndex extends Component
{
    #[Url(as: 'folder')]
    public string $folder = 'inbox';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'thread')]
    public ?string $selectedThreadKey = null;

    public string $selectedProjectId = '';

    public string $replyDraft = '';

    public bool $isSyncingGmail = false;

    public ?string $lastGmailSyncAt = null;

    public bool $readOnlyMode = true;

    public array $folders = [
        'inbox' => 'Inbox',
        'sent' => 'Sent',
        'drafts' => 'Drafts',
        'archive' => 'Archive',
        'all' => 'All Mail',
    ];

    public function mount(): void
    {
        $this->normalizeFolder();

        $user = Auth::user();
        $this->lastGmailSyncAt = $user?->gmail_import_date?->diffForHumans();
    }

    public function updatedFolder(): void
    {
        $this->normalizeFolder();
        $this->selectedThreadKey = null;
        $this->selectedProjectId = '';
        $this->replyDraft = '';
    }

    public function updatedSearch(): void
    {
        $this->selectedThreadKey = null;
        $this->selectedProjectId = '';
        $this->replyDraft = '';
    }

    public function selectThread(string $threadKey): void
    {
        $this->selectedThreadKey = $threadKey;
        $this->selectedProjectId = '';
        $this->replyDraft = '';
    }

    public function syncGmail(GoogleGmailService $gmailService): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($this->isSyncingGmail) {
            return;
        }

        $this->isSyncingGmail = true;

        try {
            $result = $gmailService->syncRecentMessages($user, 90, 300);

            if (! ($result['connected'] ?? false)) {
                $this->dispatch('notify', type: 'warning', message: 'Google is not connected. Connect Google first.');
            } else {
                $count = (int) ($result['processed'] ?? 0);
                $imported = (int) ($result['imported'] ?? 0);
                $updated = (int) ($result['updated'] ?? 0);
                $this->dispatch('notify', type: 'success', message: "Gmail synced ({$count} processed, {$imported} new, {$updated} updated).");
            }
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: 'Gmail sync failed: '.$exception->getMessage());
        } finally {
            $user->refresh();
            $this->lastGmailSyncAt = $user->gmail_import_date?->diffForHumans();
            $this->isSyncingGmail = false;
        }
    }

    public function createFollowUpTask(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $counterpart = (string) ($snapshot['counterpart_name'] ?? 'contact');
        $subject = (string) ($snapshot['subject'] ?? 'Email follow-up');

        $dueDate = now()->addDay();
        if (($snapshot['sentiment'] ?? 'neutral') === 'critical') {
            $dueDate = now();
        }

        $descriptionParts = [
            'Generated from Inbox thread.',
            'Subject: '.$subject,
        ];

        if (! empty($snapshot['counterpart_email'])) {
            $descriptionParts[] = 'Counterpart: '.$counterpart.' <'.$snapshot['counterpart_email'].'>';
        } else {
            $descriptionParts[] = 'Counterpart: '.$counterpart;
        }

        if (! empty($snapshot['latest_snippet'])) {
            $descriptionParts[] = 'Context: '.$snapshot['latest_snippet'];
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId > 0) {
            $projectName = Project::query()->whereKey($projectId)->value('name');
            if ($projectName) {
                $descriptionParts[] = 'Linked project: '.$projectName;
            }
        }

        Action::query()->create([
            'title' => Str::limit('Follow up with '.$counterpart.' re: '.$subject, 240),
            'description' => implode("\n", $descriptionParts),
            'due_date' => $dueDate->toDateString(),
            'priority' => ($snapshot['sentiment'] ?? 'neutral') === 'critical' ? Action::PRIORITY_HIGH : Action::PRIORITY_MEDIUM,
            'status' => Action::STATUS_PENDING,
            'source' => Action::SOURCE_AI_SUGGESTED,
            'assigned_to' => $user->id,
            'project_id' => $projectId > 0 ? $projectId : null,
        ]);

        $person = $snapshot['person'] ?? null;
        if ($person instanceof Person) {
            PersonInteraction::query()->create([
                'person_id' => $person->id,
                'user_id' => $user->id,
                'type' => 'email',
                'occurred_at' => now(),
                'summary' => 'Follow-up task created from Inbox thread: '.Str::limit($subject, 140),
                'next_action_at' => $dueDate,
                'next_action_note' => 'Reply to '.$counterpart,
            ]);
        }

        $this->dispatch('notify', type: 'success', message: 'Follow-up task created.');
    }

    public function linkPersonToProject(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $projectId = (int) $this->selectedProjectId;
        if ($projectId <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Choose a project first.');

            return;
        }

        $person = $snapshot['person'] ?? null;
        if (! $person instanceof Person) {
            $this->dispatch('notify', type: 'error', message: 'No contact is linked to this thread yet.');

            return;
        }

        $project = Project::query()->find($projectId);
        if (! $project) {
            $this->dispatch('notify', type: 'error', message: 'Selected project was not found.');

            return;
        }

        $project->people()->syncWithoutDetaching([
            $person->id => [
                'role' => 'email counterpart',
                'notes' => 'Linked from Inbox thread: '.Str::limit((string) ($snapshot['subject'] ?? ''), 180),
            ],
        ]);

        $this->dispatch('notify', type: 'success', message: 'Contact linked to project.');
    }

    public function createProjectFromThread(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $subject = (string) ($snapshot['subject'] ?? 'New initiative');
        $counterpart = (string) ($snapshot['counterpart_name'] ?? 'email thread');

        $name = $this->deriveProjectNameFromSubject($subject);

        $project = Project::query()->create([
            'name' => $name,
            'status' => 'planning',
            'project_type' => 'initiative',
            'created_by' => $user->id,
            'description' => 'Created from Inbox thread with '.$counterpart.'.',
            'tags' => ['inbox-origin'],
        ]);

        $person = $snapshot['person'] ?? null;
        if ($person instanceof Person) {
            $project->people()->syncWithoutDetaching([
                $person->id => [
                    'role' => 'contact',
                    'notes' => 'Added at project creation from Inbox thread.',
                ],
            ]);
        }

        $this->selectedProjectId = (string) $project->id;

        $this->dispatch('notify', type: 'success', message: 'Project created and linked.');
    }

    public function generateReplyDraft(): void
    {
        $snapshot = $this->selectedThreadSnapshot();
        if (! $snapshot) {
            $this->dispatch('notify', type: 'error', message: 'Select a thread first.');

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $name = (string) ($snapshot['counterpart_name'] ?? 'there');
        $subject = (string) ($snapshot['subject'] ?? 'your note');
        $sentiment = (string) ($snapshot['sentiment'] ?? 'neutral');

        $opening = match ($sentiment) {
            'critical' => 'Thank you for flagging this. I appreciate the detailed context and want to address it carefully.',
            'positive' => 'Thanks for this update. Great to hear your momentum on this.',
            default => 'Thanks for your note. I received this and am following up now.',
        };

        $this->replyDraft = "Hi {$name},\n\n"
            .$opening."\n\n"
            .'I reviewed your email regarding "'.$subject.'" and will follow up with concrete next steps shortly.'
            ."\n\nBest,\n{$user->name}";
    }

    public function clearReplyDraft(): void
    {
        $this->replyDraft = '';
    }

    public function getFolderCountsProperty(): array
    {
        $messages = $this->baseMessages();

        return [
            'inbox' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'inbox'))->count(),
            'sent' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'sent'))->count(),
            'drafts' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'drafts'))->count(),
            'archive' => $messages->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, 'archive'))->count(),
            'all' => $messages->count(),
        ];
    }

    public function getThreadSummariesProperty(): array
    {
        return $this->groupedMessages()
            ->map(function (Collection $messages, string $threadKey) {
                $latest = $messages
                    ->sortByDesc(fn (GmailMessage $message) => $message->sent_at?->timestamp ?? 0)
                    ->first();

                if (! $latest) {
                    return null;
                }

                $counterpart = $this->counterpartForMessage($latest);
                $sentiment = $this->threadSentiment($messages);

                return [
                    'thread_key' => $threadKey,
                    'subject' => $latest->subject ?: '(No subject)',
                    'preview' => $latest->snippet ?: 'No preview available.',
                    'date_label' => $this->formatThreadDate($latest->sent_at),
                    'counterpart_name' => $counterpart['name'],
                    'counterpart_email' => $counterpart['email'],
                    'message_count' => $messages->count(),
                    'labels' => $this->visibleLabels($latest->labels ?? []),
                    'sentiment' => $sentiment,
                    'sort_at' => $latest->sent_at?->timestamp ?? 0,
                ];
            })
            ->filter()
            ->sortByDesc('sort_at')
            ->values()
            ->all();
    }

    public function getSelectedThreadProperty(): ?array
    {
        return $this->selectedThreadSnapshot();
    }

    protected function selectedThreadSnapshot(): ?array
    {
        $groups = $this->groupedMessages();
        if ($groups->isEmpty()) {
            return null;
        }

        if (! $this->selectedThreadKey || ! $groups->has($this->selectedThreadKey)) {
            $this->selectedThreadKey = (string) $groups->keys()->first();
            $this->selectedProjectId = '';
            $this->replyDraft = '';
        }

        $messages = $groups->get($this->selectedThreadKey);
        if (! $messages instanceof Collection || $messages->isEmpty()) {
            return null;
        }

        $messages = $messages
            ->sortBy(fn (GmailMessage $message) => $message->sent_at?->timestamp ?? 0)
            ->values();

        /** @var GmailMessage|null $latest */
        $latest = $messages->last();
        if (! $latest) {
            return null;
        }

        $person = $latest->person;
        $organization = $person?->organization;
        $projectCandidates = $this->projectCandidates($messages, $person)->values();

        if ($projectCandidates->isNotEmpty()) {
            $candidateIds = $projectCandidates->pluck('id')->map(fn ($id) => (string) $id)->all();
            if ($this->selectedProjectId === '' || ! in_array((string) $this->selectedProjectId, $candidateIds, true)) {
                $this->selectedProjectId = (string) $projectCandidates->first()['id'];
            }
        } else {
            $this->selectedProjectId = '';
        }

        $isPersonLinkedToSelectedProject = false;
        if ($person && $this->selectedProjectId !== '') {
            $isPersonLinkedToSelectedProject = $person->projects()
                ->where('projects.id', (int) $this->selectedProjectId)
                ->exists();
        }

        $counterpart = $this->counterpartForMessage($latest);
        $analysis = $this->agentAnalysis($messages, $latest, $projectCandidates, $person, $organization);

        $suggestions = $this->threadSuggestions(
            $messages,
            $latest,
            $projectCandidates,
            $person,
            $organization,
            $isPersonLinkedToSelectedProject
        );

        return [
            'thread_key' => $this->selectedThreadKey,
            'subject' => $latest->subject ?: '(No subject)',
            'messages' => $messages->map(function (GmailMessage $message) {
                $counterpart = $this->counterpartForMessage($message);

                return [
                    'id' => $message->id,
                    'is_inbound' => (bool) $message->is_inbound,
                    'sender_name' => $counterpart['name'],
                    'sender_email' => $counterpart['email'],
                    'snippet' => $message->snippet ?: 'No message snippet available.',
                    'sent_label' => $this->formatMessageDateTime($message->sent_at),
                    'labels' => $this->visibleLabels($message->labels ?? []),
                ];
            })->all(),
            'person' => $person,
            'organization' => $organization,
            'counterpart_name' => $counterpart['name'],
            'counterpart_email' => $counterpart['email'],
            'latest_snippet' => $latest->snippet,
            'sentiment' => $this->threadSentiment($messages),
            'project_candidates' => $projectCandidates->all(),
            'is_person_linked_to_selected_project' => $isPersonLinkedToSelectedProject,
            'agent_analysis' => $analysis,
            'suggestions' => $suggestions,
            'latest_sent_at' => $latest->sent_at,
        ];
    }

    protected function threadSuggestions(
        Collection $messages,
        GmailMessage $latest,
        Collection $projectCandidates,
        ?Person $person,
        $organization,
        bool $isPersonLinkedToSelectedProject
    ): array {
        $suggestions = [
            [
                'key' => 'create_follow_up_task',
                'title' => 'Create follow-up task',
                'body' => 'Capture this thread as a tracked follow-up action in WRK.',
                'action' => 'createFollowUpTask',
                'button' => 'Create task',
            ],
            [
                'key' => 'generate_reply_draft',
                'title' => 'Draft reply',
                'body' => 'Generate a first-pass response you can edit before sending outside WRK.',
                'action' => 'generateReplyDraft',
                'button' => 'Generate draft',
            ],
        ];

        if ($person && $this->selectedProjectId !== '' && ! $isPersonLinkedToSelectedProject) {
            $suggestions[] = [
                'key' => 'link_person_project',
                'title' => 'Link contact to selected project',
                'body' => $person->name.' is not yet linked to the selected project. Add them as an email counterpart.',
                'action' => 'linkPersonToProject',
                'button' => 'Link contact',
            ];
        }

        $shouldSuggestProject = $projectCandidates->isEmpty() && $this->looksLikeProjectThread($messages, $latest, $organization);
        if ($shouldSuggestProject) {
            $suggestions[] = [
                'key' => 'create_project',
                'title' => 'Create project from thread',
                'body' => 'No clear project match found. Create a planning project and link this thread context.',
                'action' => 'createProjectFromThread',
                'button' => 'Create project',
            ];
        }

        return $suggestions;
    }

    protected function projectCandidates(Collection $messages, ?Person $person): Collection
    {
        $allProjects = Project::query()
            ->whereIn('status', ['planning', 'active', 'on_hold'])
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'status']);

        $messageText = Str::lower(
            $messages
                ->take(-8)
                ->map(fn (GmailMessage $message) => trim((string) ($message->subject.' '.$message->snippet)))
                ->implode(' ')
        );

        $matchedByText = $allProjects
            ->filter(fn (Project $project) => $this->projectMentionedInText($project->name, $messageText))
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'source' => 'thread_match',
            ]);

        $matchedByPerson = collect();
        if ($person) {
            $matchedByPerson = $person->projects()
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->orderBy('projects.name')
                ->limit(12)
                ->get(['projects.id', 'projects.name', 'projects.status'])
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'source' => 'contact_link',
                ]);
        }

        return $matchedByPerson
            ->concat($matchedByText)
            ->unique('id')
            ->values();
    }

    protected function projectMentionedInText(string $projectName, string $messageText): bool
    {
        $name = Str::of(Str::lower($projectName))
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();

        if ($name === '' || mb_strlen($name) < 4) {
            return false;
        }

        if (Str::contains($messageText, $name)) {
            return true;
        }

        $parts = collect(explode(' ', $name))
            ->filter(fn (string $part) => mb_strlen($part) >= 4)
            ->values();

        if ($parts->count() < 2) {
            return false;
        }

        $matches = $parts->filter(fn (string $part) => Str::contains($messageText, $part))->count();

        return $matches >= 2;
    }

    protected function looksLikeProjectThread(Collection $messages, GmailMessage $latest, $organization): bool
    {
        $text = Str::lower($messages->take(-8)->map(fn (GmailMessage $message) => ($message->subject ?? '').' '.($message->snippet ?? ''))->implode(' '));

        $keywords = [
            'project', 'initiative', 'proposal', 'pilot', 'phase', 'workplan', 'scope',
            'deliverable', 'timeline', 'kickoff', 'partnership', 'collaboration', 'contract',
        ];

        $hasKeyword = collect($keywords)->contains(fn (string $keyword) => Str::contains($text, $keyword));

        if (! $hasKeyword) {
            return false;
        }

        if ($organization) {
            $existing = Project::query()
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organization->id))
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->count();

            if ($existing > 0) {
                return false;
            }
        }

        return true;
    }

    protected function agentAnalysis(
        Collection $messages,
        GmailMessage $latest,
        Collection $projectCandidates,
        ?Person $person,
        $organization
    ): string {
        $sentiment = $this->threadSentiment($messages);

        $parts = [];
        if ($sentiment === 'critical') {
            $parts[] = 'This thread has urgency/conflict signals and should be handled quickly.';
        } elseif ($sentiment === 'positive') {
            $parts[] = 'This thread appears constructive and may be a momentum opportunity.';
        } else {
            $parts[] = 'This thread looks informational and suitable for standard follow-up.';
        }

        if ($projectCandidates->isNotEmpty()) {
            $parts[] = 'Likely linked project: '.$projectCandidates->first()['name'].'.';
        } else {
            $parts[] = 'No strong project match found yet.';
        }

        if ($person) {
            $parts[] = $person->name.' is recognized in Contacts.';
        } else {
            $parts[] = 'Counterpart is not yet linked to a contact record.';
        }

        if ($organization) {
            $parts[] = 'Organization context: '.$organization->name.'.';
        }

        if ($latest->is_inbound) {
            $parts[] = 'Recommended next step: create a follow-up task and draft a reply.';
        } else {
            $parts[] = 'Recommended next step: track expected response and due date.';
        }

        return implode(' ', $parts);
    }

    protected function threadSentiment(Collection $messages): string
    {
        $text = Str::lower(
            $messages
                ->take(-8)
                ->map(fn (GmailMessage $message) => ($message->subject ?? '').' '.($message->snippet ?? ''))
                ->implode(' ')
        );

        $criticalWords = ['urgent', 'concern', 'problem', 'issue', 'blocked', 'conflict', 'risk', 'delay'];
        if (collect($criticalWords)->contains(fn (string $word) => Str::contains($text, $word))) {
            return 'critical';
        }

        $positiveWords = ['great', 'excited', 'thanks', 'appreciate', 'love', 'opportunity', 'happy'];
        if (collect($positiveWords)->contains(fn (string $word) => Str::contains($text, $word))) {
            return 'positive';
        }

        return 'neutral';
    }

    protected function counterpartForMessage(GmailMessage $message): array
    {
        if ($message->person) {
            return [
                'name' => $message->person->name,
                'email' => $message->person->email ?: ($message->from_email ?: ''),
            ];
        }

        $name = trim((string) ($message->from_name ?: ''));
        $email = trim((string) ($message->from_email ?: ''));

        if ($name === '' && $email !== '') {
            $name = Str::before($email, '@');
        }

        if ($name === '') {
            $name = 'Unknown sender';
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }

    protected function deriveProjectNameFromSubject(string $subject): string
    {
        $clean = Str::of($subject)
            ->replaceMatches('/^\s*(re|fwd|fw)\s*:\s*/i', '')
            ->replaceMatches('/[^A-Za-z0-9\s\-\/]/', '')
            ->squish()
            ->value();

        if ($clean === '') {
            return 'New Project from Inbox';
        }

        return Str::limit($clean, 100, '');
    }

    protected function groupedMessages(): Collection
    {
        return $this->filteredMessages()->groupBy(fn (GmailMessage $message) => $this->threadKeyForMessage($message));
    }

    protected function filteredMessages(): Collection
    {
        $messages = $this->baseMessages()->filter(fn (GmailMessage $message) => $this->messageMatchesFolder($message, $this->folder));

        $query = trim($this->search);
        if ($query === '') {
            return $messages->values();
        }

        $needle = Str::lower($query);

        return $messages
            ->filter(function (GmailMessage $message) use ($needle) {
                $haystack = Str::lower(implode(' ', [
                    (string) $message->subject,
                    (string) $message->snippet,
                    (string) $message->from_name,
                    (string) $message->from_email,
                    (string) ($message->person?->name ?? ''),
                    (string) ($message->person?->email ?? ''),
                    (string) ($message->person?->organization?->name ?? ''),
                ]));

                return Str::contains($haystack, $needle);
            })
            ->values();
    }

    protected function baseMessages(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return GmailMessage::query()
            ->where('user_id', $user->id)
            ->with(['person.organization'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit(1200)
            ->get();
    }

    protected function threadKeyForMessage(GmailMessage $message): string
    {
        $threadId = trim((string) $message->gmail_thread_id);
        if ($threadId !== '') {
            return $threadId;
        }

        return 'msg:'.(string) $message->gmail_message_id;
    }

    protected function messageMatchesFolder(GmailMessage $message, string $folder): bool
    {
        return match ($folder) {
            'inbox' => $this->messageHasLabel($message, 'INBOX'),
            'sent' => $this->messageHasLabel($message, 'SENT'),
            'drafts' => $this->messageHasLabel($message, 'DRAFT'),
            'archive' => ! $this->messageHasLabel($message, 'INBOX')
                && ! $this->messageHasLabel($message, 'TRASH')
                && ! $this->messageHasLabel($message, 'SPAM')
                && ! $this->messageHasLabel($message, 'DRAFT'),
            'all' => true,
            default => $this->messageHasLabel($message, 'INBOX'),
        };
    }

    protected function messageHasLabel(GmailMessage $message, string $label): bool
    {
        $labels = collect($message->labels ?? [])
            ->map(fn ($value) => Str::upper(trim((string) $value)))
            ->filter()
            ->all();

        return in_array(Str::upper($label), $labels, true);
    }

    protected function visibleLabels(array $labels): array
    {
        $ignored = ['UNREAD', 'CATEGORY_PERSONAL', 'CATEGORY_UPDATES', 'CATEGORY_PROMOTIONS', 'IMPORTANT'];

        return collect($labels)
            ->map(fn ($label) => Str::upper((string) $label))
            ->reject(fn ($label) => in_array($label, $ignored, true))
            ->take(3)
            ->values()
            ->all();
    }

    protected function formatThreadDate(?Carbon $date): string
    {
        if (! $date) {
            return 'No date';
        }

        if ($date->isToday()) {
            return $date->format('g:i A');
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        if ($date->year === now()->year) {
            return $date->format('M j');
        }

        return $date->format('M j, Y');
    }

    protected function formatMessageDateTime(?Carbon $date): string
    {
        if (! $date) {
            return 'No date';
        }

        return $date->format('M j, Y g:i A');
    }

    protected function normalizeFolder(): void
    {
        if (! array_key_exists($this->folder, $this->folders)) {
            $this->folder = 'inbox';
        }
    }

    public function render()
    {
        $threadSummaries = $this->threadSummaries;
        $selected = $this->selectedThread;

        return view('livewire.communications.inbox-index', [
            'threadSummaries' => $threadSummaries,
            'selectedThread' => $selected,
            'folderCounts' => $this->folderCounts,
            'hasMessages' => ! empty($threadSummaries),
        ]);
    }
}
