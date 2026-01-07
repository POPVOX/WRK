<?php

namespace App\Livewire\Projects;

use App\Jobs\FetchLinkContent;
use App\Jobs\IndexDocumentContent;
use App\Jobs\RunStyleCheck;
use App\Jobs\SendChatMessage;
use App\Jobs\SuggestDocumentTags;
use App\Models\Project;
use App\Models\ProjectChatMessage;
use App\Models\ProjectDocument;
use App\Models\ProjectEvent;
use App\Models\ProjectMilestone;
use App\Models\ProjectPublication;
use App\Services\DocumentSafety;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ProjectWorkspace extends Component
{
    use WithFileUploads;

    public Project $project;

    public string $activeTab = 'overview';

    // AI Chat
    public string $chatQuery = '';

    public bool $isProcessing = false;

    public array $chatHistory = [];

    // Event Creation
    public bool $showEventModal = false;

    public string $newEventTitle = '';

    public string $newEventType = 'staff_event';

    public ?string $newEventDate = null;

    public string $newEventLocation = '';

    public string $newEventDescription = '';

    public ?int $newEventTargetAttendees = null;

    // Document Viewer
    public bool $showDocumentViewer = false;

    public ?int $viewingDocumentId = null;

    public string $documentContent = '';

    public string $documentTitle = '';

    // Upload / Link Inputs
    public $uploadFile = null;

    public string $uploadTitle = '';

    public string $linkTitle = '';

    public string $linkUrl = '';

    // Style Check
    public bool $isStyleChecking = false;

    public array $styleCheckSuggestions = [];

    public bool $styleCheckComplete = false;

    // Notices & Flags
    public bool $aiEnabled = true;

    public ?string $aiNotice = null;

    public ?string $styleNotice = null;

    public ?string $authNotice = null;

    // Sync Preview
    public bool $showSyncPreviewModal = false;

    public array $syncPreview = [
        'add' => [],
        'update' => [],
        'missing' => [],
    ];

    // Knowledge Base Search
    public string $kbQuery = '';

    public array $kbResults = [];

    public bool $kbIsSearching = false;

    // Inline tags editing
    public array $tagsEdit = [];

    public array $commonTags = [];

    protected $queryString = ['activeTab'];

    public function mount(Project $project)
    {
        $this->project = $project->load([
            'workstreams',
            'publications',
            'events',
            'milestones',
            'documents',
            'meetings' => fn ($q) => $q->latest('meeting_date')->limit(5),
        ]);

        // Flags
        $this->aiEnabled = (bool) config('ai.enabled');

        // Load chat history
        $this->loadChatHistory();

        // Aggregate common tags for autocomplete
        $this->refreshCommonTags();

        // Open document viewer from deep link
        $docToOpen = (int) request()->query('doc', 0);
        if ($docToOpen > 0) {
            try {
                $this->viewDocument($docToOpen);
            } catch (\Throwable $e) {
                // ignore invalid ids
            }
        }
    }

    protected function loadChatHistory(): void
    {
        $messages = $this->project->chatMessages()
            ->where('user_id', Auth::id())
            ->orderBy('created_at')
            ->get();

        $this->chatHistory = $messages->map(fn ($m) => [
            'role' => $m->role,
            'content' => $m->content,
            'timestamp' => $m->created_at->format('g:i A'),
        ])->toArray();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // AI Chat Methods
    public function sendChat(): void
    {
        if (empty(trim($this->chatQuery))) {
            return;
        }

        // Authorization
        if (! $this->authorizeInitiative('chat')) {
            $this->aiNotice = $this->authNotice ?? 'You do not have permission to use AI chat.';

            return;
        }

        // Feature flag: AI disabled
        if (! config('ai.enabled')) {
            $this->aiNotice = 'AI features are disabled by the administrator.';

            return;
        }

        // Rate limiting: 30 req / 60s per user+project
        $chatLimit = config('ai.limits.chat');
        $chatKey = 'ai-chat:'.Auth::id().':'.$this->project->id;
        if (RateLimiter::tooManyAttempts($chatKey, $chatLimit['max'])) {
            $this->aiNotice = 'You are sending messages too quickly. Please wait a moment and try again.';

            return;
        }
        RateLimiter::hit($chatKey, $chatLimit['decay_seconds']);

        $this->isProcessing = true;
        $query = $this->chatQuery;
        $this->chatQuery = '';

        // Save user message
        ProjectChatMessage::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $query,
        ]);

        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $query,
            'timestamp' => now()->format('g:i A'),
        ];

        // Dispatch background job for AI response
        SendChatMessage::dispatch($this->project->id, Auth::id(), $query, $this->getSystemPrompt());

        $this->isProcessing = false;

        // Let UI scroll now and start polling for the assistant reply
        $this->dispatch('chatUpdated');
        $this->dispatch('chatStarted');
    }

    protected function getSystemPrompt(): string
    {
        $projectContext = $this->buildProjectContext();

        return <<<PROMPT
You are an AI collaborator helping with the project "{$this->project->name}".

## Your Role
You are a knowledgeable assistant helping plan, organize, and execute this project. You have access to all project documents and context.

## Project Context
{$projectContext}

## Guidelines
- Be helpful, concise, and actionable
- Reference specific documents or sections when relevant
- Suggest next steps when appropriate
- Help with planning, content drafting, research, and analysis
- When suggesting changes, be specific about what to modify
- If you don't know something, say so clearly
PROMPT;
    }

    protected function buildProjectContext(): string
    {
        $context = [];

        // Basic project info
        $context[] = "**Project Name:** {$this->project->name}";
        $context[] = '**Description:** '.($this->project->description ?? 'No description');
        $context[] = "**Status:** {$this->project->status}";

        // Publications summary
        $pubCount = $this->project->publications->count();
        $publishedCount = $this->project->publications->where('status', 'published')->count();
        $context[] = "**Publications:** {$publishedCount}/{$pubCount} published";

        // Events summary
        $eventCount = $this->project->events->count();
        $completedEvents = $this->project->events->where('status', 'completed')->count();
        $context[] = "**Events:** {$completedEvents}/{$eventCount} completed";

        // Load project documents content
        if ($this->project->project_path) {
            $projectDir = base_path($this->project->project_path);

            // Try to load README
            $readmePath = $projectDir.'/README.md';
            if (file_exists($readmePath)) {
                $readme = file_get_contents($readmePath);
                $context[] = "\n## Project README\n".$readme;
            }

            // Try to load TIMELINE
            $timelinePath = $projectDir.'/TIMELINE.md';
            if (file_exists($timelinePath)) {
                $timeline = file_get_contents($timelinePath);
                $context[] = "\n## Project Timeline\n".$timeline;
            }

            // Try to load CHAPTERS
            $chaptersPath = $projectDir.'/CHAPTERS.md';
            if (file_exists($chaptersPath)) {
                $chapters = file_get_contents($chaptersPath);
                $context[] = "\n## Chapters Outline\n".$chapters;
            }
        }

        return implode("\n", $context);
    }

    public function clearChat(): void
    {
        ProjectChatMessage::where('project_id', $this->project->id)
            ->where('user_id', Auth::id())
            ->delete();

        $this->chatHistory = [];
    }

    public function refreshChatHistory(): void
    {
        $this->loadChatHistory();
        $this->dispatch('chatUpdated');
    }

    protected function authorizeInitiative(string $action): bool
    {
        $user = Auth::user();
        if (! $user) {
            $this->authNotice = 'You must be signed in.';

            return false;
        }

        // Admins always allowed
        if (($user->is_admin ?? false) === true) {
            return true;
        }

        // Allow all authenticated users to access enhanced workspace features
        return true;
    }

    // Queued Style Check: dispatch job or load cached results by content hash
    public function runStyleCheckQueued(): void
    {
        if (! $this->viewingDocumentId) {
            return;
        }

        // Authorization
        if (! $this->authorizeInitiative('style-check')) {
            $this->styleNotice = $this->authNotice ?? 'You do not have permission to run style checks.';

            return;
        }

        // Feature flag: AI disabled
        if (! config('ai.enabled')) {
            $this->styleNotice = 'AI features are disabled by the administrator.';

            return;
        }

        // Rate limiting: 10 req / 5min per user+project
        $limit = config('ai.limits.style_check');
        $key = 'ai-style:'.Auth::id().':'.$this->project->id;
        if (RateLimiter::tooManyAttempts($key, $limit['max'])) {
            $this->styleNotice = 'Too many style checks. Please try again in a few minutes.';

            return;
        }
        RateLimiter::hit($key, $limit['decay_seconds']);

        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $fullPath = base_path($document->file_path);
        // Ensure file exists and is within base path
        if (! file_exists($fullPath) || ! \App\Services\DocumentSafety::withinBase(base_path(), $fullPath)) {
            return;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (! in_array($ext, ['md', 'markdown', 'txt'], true)) {
            // Only text-like docs are supported for style check
            return;
        }

        $content = file_get_contents($fullPath) ?: '';
        $this->documentContent = $content;
        $this->documentTitle = $document->title ?? basename($fullPath);

        $hash = DocumentSafety::hashContent($content);
        $cachePath = "style_checks/{$document->id}-{$hash}.json";

        if (Storage::disk('local')->exists($cachePath)) {
            $payload = json_decode(Storage::disk('local')->get($cachePath), true) ?: [];
            $this->styleCheckSuggestions = $payload['suggestions'] ?? [];
            // Initialize status fields if not present
            $this->styleCheckSuggestions = array_map(function ($s) {
                $s['status'] = $s['status'] ?? 'pending';

                return $s;
            }, $this->styleCheckSuggestions);
            $this->styleCheckComplete = true;
            $this->isStyleChecking = false;
        } else {
            $this->isStyleChecking = true;
            $this->styleCheckComplete = false;
            $this->styleCheckSuggestions = [];
            RunStyleCheck::dispatch($this->project->id, $document->id, $document->file_path, $content);
            $this->dispatch('styleCheckStarted');
        }
    }

    public function checkStyleCheckStatus(): void
    {
        if (! $this->viewingDocumentId || empty($this->documentContent)) {
            return;
        }
        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $hash = DocumentSafety::hashContent($this->documentContent);
        $cachePath = "style_checks/{$document->id}-{$hash}.json";

        if (Storage::disk('local')->exists($cachePath)) {
            $payload = json_decode(Storage::disk('local')->get($cachePath), true) ?: [];
            $this->styleCheckSuggestions = $payload['suggestions'] ?? [];
            $this->styleCheckSuggestions = array_map(function ($s) {
                $s['status'] = $s['status'] ?? 'pending';

                return $s;
            }, $this->styleCheckSuggestions);

            $this->styleCheckComplete = true;
            $this->isStyleChecking = false;
            $this->dispatch('styleCheckCompleted');
        }
    }

    public function saveDocumentTags(int $documentId): void
    {
        if (! $this->authorizeInitiative('update-document-tags')) {
            return;
        }

        $doc = ProjectDocument::find($documentId);
        if (! $doc || $doc->project_id !== $this->project->id) {
            return;
        }

        $raw = $this->tagsEdit[$documentId] ?? '';
        $tags = collect(explode(',', (string) $raw))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $doc->tags = $tags;
        $doc->save();

        // Reindex KB to include updated tags
        \App\Jobs\IndexDocumentContent::dispatch($doc->id);

        // Clear input and refresh
        unset($this->tagsEdit[$documentId]);
        $this->project->refresh();
        $this->project->load('documents');
        $this->refreshCommonTags();
    }

    public function addSuggestedTag(int $documentId, string $tag): void
    {
        if (! $this->authorizeInitiative('update-document-tags')) {
            return;
        }

        $doc = ProjectDocument::find($documentId);
        if (! $doc || $doc->project_id !== $this->project->id) {
            return;
        }

        $tags = collect($doc->tags ?? [])->merge([$tag])->map(fn ($t) => trim($t))->filter()->unique()->values()->all();
        $doc->tags = $tags;
        $doc->save();

        \App\Jobs\IndexDocumentContent::dispatch($doc->id);

        $this->project->refresh();
        $this->project->load('documents');
        $this->refreshCommonTags();
    }

    protected function refreshCommonTags(): void
    {
        $tags = [];
        foreach ($this->project->documents as $d) {
            foreach ((array) ($d->tags ?? []) as $t) {
                $t = trim((string) $t);
                if ($t !== '') {
                    $tags[$t] = ($tags[$t] ?? 0) + 1;
                }
            }
        }
        arsort($tags);
        $this->commonTags = array_slice(array_keys($tags), 0, 20);
    }

    // Publication Methods
    public function updatePublicationStatus(int $publicationId, string $status): void
    {
        if (! $this->authorizeInitiative('update-publication')) {
            return;
        }

        $publication = ProjectPublication::find($publicationId);
        if ($publication && $publication->project_id === $this->project->id) {
            $publication->update(['status' => $status]);
            if ($status === 'published') {
                $publication->update(['published_date' => now()]);
            }
        }
    }

    // Event Methods
    public function updateEventStatus(int $eventId, string $status): void
    {
        if (! $this->authorizeInitiative('update-event')) {
            return;
        }

        $event = ProjectEvent::find($eventId);
        if ($event && $event->project_id === $this->project->id) {
            $event->update(['status' => $status]);
        }
    }

    public function openEventModal(): void
    {
        if (! $this->authorizeInitiative('create-event')) {
            return;
        }

        $this->showEventModal = true;
        $this->resetEventForm();
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->resetEventForm();
    }

    protected function resetEventForm(): void
    {
        $this->newEventTitle = '';
        $this->newEventType = 'staff_event';
        $this->newEventDate = null;
        $this->newEventLocation = '';
        $this->newEventDescription = '';
        $this->newEventTargetAttendees = null;
    }

    public function createEvent(): void
    {
        if (! $this->authorizeInitiative('create-event')) {
            return;
        }

        $this->validate([
            'newEventTitle' => 'required|min:3',
            'newEventType' => 'required|in:'.implode(',', array_keys(ProjectEvent::TYPES)),
            'newEventDate' => 'nullable|date',
            'newEventLocation' => 'nullable|string|max:255',
            'newEventDescription' => 'nullable|string|max:1000',
            'newEventTargetAttendees' => 'nullable|integer|min:1',
        ]);

        ProjectEvent::create([
            'project_id' => $this->project->id,
            'title' => $this->newEventTitle,
            'type' => $this->newEventType,
            'event_date' => $this->newEventDate,
            'location' => $this->newEventLocation,
            'description' => $this->newEventDescription,
            'target_attendees' => $this->newEventTargetAttendees,
            'status' => 'planning',
        ]);

        $this->project->refresh();
        $this->project->load('events');
        $this->closeEventModal();
    }

    // Front-end Document Upload/Link Methods
    public function uploadDocument(): void
    {
        if (! $this->authorizeInitiative('upload-document')) {
            return;
        }

        $this->validate([
            'uploadFile' => 'required|file|max:20480', // 20MB
            'uploadTitle' => 'nullable|string|max:255',
        ]);

        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        if (! DocumentSafety::isAllowedExtension($ext)) {
            $this->authNotice = 'File type not allowed.';

            return;
        }

        // Build sanitized filename
        $baseName = $this->uploadTitle !== ''
            ? \Illuminate\Support\Str::slug($this->uploadTitle)
            : pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = \Illuminate\Support\Str::limit(preg_replace('/[^A-Za-z0-9\-_]+/', '-', $baseName), 120, '').'.'.$ext;

        $dir = 'project_uploads/'.$this->project->id;
        $path = $this->uploadFile->storeAs($dir, $fileName, 'public');

        $fullPath = Storage::disk('public')->path($path);
        $hash = \App\Services\DocumentSafety::hashFile($fullPath);
        $size = @filesize($fullPath) ?: null;
        $mime = @mime_content_type($fullPath) ?: null;

        $doc = ProjectDocument::create([
            'project_id' => $this->project->id,
            'title' => $this->uploadTitle !== '' ? $this->uploadTitle : $fileName,
            'type' => 'file',
            'file_path' => $path, // relative to public disk
            'file_type' => $ext,
            'mime_type' => $mime,
            'file_size' => $size,
            'uploaded_by' => Auth::id(),
            'ai_indexed' => false,
            'content_hash' => $hash,
            'last_seen_at' => now(),
            'is_knowledge_base' => true,
        ]);

        // Index text-like uploads into the knowledge base
        if (in_array($ext, ['md', 'markdown', 'txt'], true)) {
            IndexDocumentContent::dispatch($doc->id);
            if (config('ai.enabled')) {
                SuggestDocumentTags::dispatch($doc->id);
            }
        }

        // Reset inputs and refresh docs list
        $this->uploadFile = null;
        $this->uploadTitle = '';
        $this->project->refresh();
        $this->project->load('documents');
        $this->refreshCommonTags();
    }

    public function addDocumentLink(): void
    {
        if (! $this->authorizeInitiative('add-link')) {
            return;
        }

        $this->validate([
            'linkTitle' => 'required|string|min:3|max:255',
            'linkUrl' => 'required|url|max:2048',
        ]);

        $doc = ProjectDocument::create([
            'project_id' => $this->project->id,
            'title' => $this->linkTitle,
            'type' => 'link',
            'url' => $this->linkUrl,
            'uploaded_by' => Auth::id(),
            'ai_indexed' => false,
            'is_knowledge_base' => true,
        ]);

        // Try to fetch and cache content (e.g., Google Docs -> text export)
        FetchLinkContent::dispatch($doc->id);

        $this->linkTitle = '';
        $this->linkUrl = '';
        $this->project->refresh();
        $this->project->load('documents');
    }

    // Document Sync Methods
    public function previewSyncDocumentsFromFolder(): void
    {
        if (! $this->project->project_path) {
            return;
        }
        if (! $this->authorizeInitiative('sync-documents')) {
            return;
        }

        $projectDir = base_path($this->project->project_path);
        if (! is_dir($projectDir)) {
            return;
        }

        $files = $this->scanDirectory($projectDir, DocumentSafety::allowedExtensions());
        $onDisk = [];
        foreach ($files as $file) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $onDisk[$relativePath] = [
                'size' => @filesize($file) ?: null,
                'hash' => DocumentSafety::hashFile($file),
                'title' => basename($file),
                'ext' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
            ];
        }

        $existingDocs = $this->project->documents->keyBy('file_path');

        $add = [];
        $update = [];
        $missing = [];

        // Add and update
        foreach ($onDisk as $path => $meta) {
            if (! $existingDocs->has($path)) {
                $add[] = [
                    'file_path' => $path,
                    'title' => $meta['title'],
                    'file_type' => $meta['ext'],
                    'file_size' => $meta['size'],
                ];
            } else {
                $doc = $existingDocs[$path];
                $changed = ($doc->content_hash ?? null) !== $meta['hash'] || ($doc->file_size ?? null) !== $meta['size'];
                if ($changed) {
                    $update[] = [
                        'file_path' => $path,
                        'title' => $doc->title,
                        'from_size' => $doc->file_size,
                        'to_size' => $meta['size'],
                    ];
                }
            }
        }

        // Missing (in DB but not on disk)
        foreach ($existingDocs as $doc) {
            $path = $doc->file_path;
            if (! isset($onDisk[$path])) {
                $missing[] = [
                    'file_path' => $path,
                    'title' => $doc->title,
                    'file_size' => $doc->file_size,
                ];
            }
        }

        $this->syncPreview = compact('add', 'update', 'missing');
        $this->showSyncPreviewModal = true;
    }

    public function applySyncDocumentsFromFolder(): void
    {
        if (! $this->authorizeInitiative('sync-documents')) {
            return;
        }
        $this->syncDocumentsFromFolder();
        $this->showSyncPreviewModal = false;
    }

    public function archiveMissingDocumentsFromPreview(): void
    {
        if (! $this->authorizeInitiative('sync-documents')) {
            return;
        }

        foreach ($this->syncPreview['missing'] as $entry) {
            $doc = $this->project->documents()->where('file_path', $entry['file_path'])->first();
            if ($doc) {
                $doc->update([
                    'is_archived' => true,
                    'missing_on_disk' => true,
                ]);
            }
        }

        $this->showSyncPreviewModal = false;
        $this->project->refresh();
        $this->project->load('documents');
    }

    public function removeMissingDocumentsFromPreview(): void
    {
        if (! $this->authorizeInitiative('sync-documents')) {
            return;
        }

        foreach ($this->syncPreview['missing'] as $entry) {
            $doc = $this->project->documents()->where('file_path', $entry['file_path'])->first();
            if ($doc) {
                $doc->delete();
            }
        }

        $this->showSyncPreviewModal = false;
        $this->project->refresh();
        $this->project->load('documents');
    }

    public function syncDocumentsFromFolder(): void
    {
        if (! $this->project->project_path) {
            return;
        }

        $projectDir = base_path($this->project->project_path);
        if (! is_dir($projectDir)) {
            return;
        }

        $files = $this->scanDirectory($projectDir, DocumentSafety::allowedExtensions());
        $seenPaths = [];

        foreach ($files as $file) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $seenPaths[$relativePath] = true;

            $filename = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $size = @filesize($file) ?: null;
            $hash = DocumentSafety::hashFile($file);

            $existing = $this->project->documents()->where('file_path', $relativePath)->first();

            if ($existing) {
                // Update metadata + last_seen; invalidate AI cache if content changed
                $dirty = [];
                if ($hash && $existing->content_hash !== $hash) {
                    $dirty['content_hash'] = $hash;
                    $dirty['ai_indexed'] = false;
                    $dirty['ai_summary'] = null;
                }
                if ($size !== null && $existing->file_size !== $size) {
                    $dirty['file_size'] = $size;
                }
                $dirty['last_seen_at'] = now();
                $dirty['missing_on_disk'] = false;

                if (! empty($dirty)) {
                    $existing->update($dirty);
                }

                // Auto-index text-like files when content changed
                if (in_array($extension, ['md', 'markdown', 'txt'], true) && ($dirty['content_hash'] ?? false)) {
                    IndexDocumentContent::dispatch($existing->id);
                }
            } else {
                $doc = ProjectDocument::create([
                    'project_id' => $this->project->id,
                    'title' => $filename,
                    'file_path' => $relativePath,
                    'file_type' => $extension,
                    'file_size' => $size,
                    'type' => 'file',
                    'content_hash' => $hash,
                    'last_seen_at' => now(),
                    'missing_on_disk' => false,
                    'is_knowledge_base' => true,
                ]);

                if (in_array($extension, ['md', 'markdown', 'txt'], true)) {
                    IndexDocumentContent::dispatch($doc->id);
                }
            }
        }

        // Mark documents that are no longer present on disk
        foreach ($this->project->documents as $doc) {
            if ($doc->type === 'file') {
                $isSeen = isset($seenPaths[$doc->file_path]);
                if ($doc->missing_on_disk !== ! $isSeen) {
                    $doc->update(['missing_on_disk' => ! $isSeen]);
                }
            }
        }

        $this->project->refresh();
        $this->project->load('documents');
    }

    // Knowledge Base Search
    public function searchKnowledgeBase(): void
    {
        $q = trim($this->kbQuery);
        $this->kbResults = [];
        if (mb_strlen($q) < 2) {
            return;
        }

        $this->kbIsSearching = true;

        $results = [];

        foreach ($this->project->documents as $doc) {
            // Title hit
            $titleHit = stripos($doc->title ?? '', $q) !== false;

            // KB text hit (if indexed)
            $kbHit = false;
            $snippet = null;
            if ($doc->content_hash) {
                $kbPath = "kb/{$doc->id}-{$doc->content_hash}.txt";
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($kbPath)) {
                    $text = \Illuminate\Support\Facades\Storage::disk('local')->get($kbPath);
                    $pos = stripos($text, $q);
                    if ($pos !== false) {
                        $kbHit = true;
                        $start = max(0, $pos - 40);
                        $snippet = trim(mb_substr($text, $start, 160));
                    }
                }
            }

            if ($titleHit || $kbHit) {
                $results[] = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'file_path' => $doc->file_path,
                    'type' => $doc->type,
                    'hit' => $titleHit ? 'title' : 'content',
                    'snippet' => $snippet,
                ];
            }
        }

        // Limit results for UI
        $this->kbResults = array_slice($results, 0, 25);
        $this->kbIsSearching = false;
    }

    protected function scanDirectory(string $dir, array $extensions = ['md', 'txt', 'pdf', 'docx', 'xlsx']): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // Document Viewer Methods
    public function viewDocument(int $documentId): void
    {
        $document = ProjectDocument::find($documentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $fullPath = base_path($document->file_path);
        if (! file_exists($fullPath)) {
            return;
        }

        $this->viewingDocumentId = $documentId;
        $this->documentTitle = $document->title;
        $this->documentContent = file_get_contents($fullPath);
        $this->showDocumentViewer = true;
        $this->styleCheckSuggestions = [];
        $this->styleCheckComplete = false;
    }

    public function closeDocumentViewer(): void
    {
        $this->showDocumentViewer = false;
        $this->viewingDocumentId = null;
        $this->documentContent = '';
        $this->documentTitle = '';
        $this->styleCheckSuggestions = [];
        $this->styleCheckComplete = false;
    }

    // Style Check Methods
    public function runStyleCheck(): void
    {
        if (empty($this->documentContent) || $this->isStyleChecking) {
            return;
        }

        $this->isStyleChecking = true;
        $this->styleCheckSuggestions = [];
        $this->styleCheckComplete = false;

        try {
            $styleGuide = $this->getStyleGuideContent();

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4000,
                'system' => $this->getStyleCheckSystemPrompt($styleGuide),
                'messages' => [
                    ['role' => 'user', 'content' => "Please review this document and identify any style guide violations:\n\n".$this->documentContent],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['content'][0]['text'] ?? '';

                // Parse suggestions from AI response
                $this->styleCheckSuggestions = $this->parseSuggestionsFromAI($aiResponse);
            }
        } catch (\Exception $e) {
            // Handle error silently for now
        }

        $this->isStyleChecking = false;
        $this->styleCheckComplete = true;
    }

    protected function getStyleGuideContent(): string
    {
        $stylePath = base_path('POPVOX Foundation Style Guide.md');
        if (file_exists($stylePath)) {
            return file_get_contents($stylePath);
        }

        return '';
    }

    protected function getStyleCheckSystemPrompt(string $styleGuide): string
    {
        return <<<PROMPT
You are a professional editor reviewing documents against the POPVOX Foundation Style Guide.

## Style Guide
{$styleGuide}

## Your Task
Review the document and identify specific style guide violations. For each violation found, provide a structured suggestion.

## Response Format
Return your findings as a JSON array of suggestions. Each suggestion should have:
- "original": The exact text that needs to change (be precise, include enough context)
- "replacement": The corrected text
- "rule": Brief description of which style rule this violates
- "importance": "high", "medium", or "low"

IMPORTANT: Return ONLY a valid JSON array. No other text before or after.

Example response:
[
  {"original": "the Democrat party", "replacement": "the Democratic party", "rule": "Use 'Democratic' not 'Democrat' for the party name", "importance": "high"},
  {"original": "U.S.", "replacement": "US", "rule": "Use US without periods", "importance": "medium"}
]

If no violations are found, return an empty array: []
PROMPT;
    }

    protected function parseSuggestionsFromAI(string $response): array
    {
        // Try to extract JSON from the response
        $response = trim($response);

        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $response = trim($matches[1]);
        }

        $suggestions = json_decode($response, true);

        if (! is_array($suggestions)) {
            return [];
        }

        // Add status field to each suggestion
        return array_map(function ($suggestion, $index) {
            return [
                'id' => $index,
                'original' => $suggestion['original'] ?? '',
                'replacement' => $suggestion['replacement'] ?? '',
                'rule' => $suggestion['rule'] ?? '',
                'importance' => $suggestion['importance'] ?? 'medium',
                'status' => 'pending', // pending, accepted, rejected
            ];
        }, $suggestions, array_keys($suggestions));
    }

    public function acceptSuggestion(int $index): void
    {
        if (isset($this->styleCheckSuggestions[$index])) {
            $this->styleCheckSuggestions[$index]['status'] = 'accepted';
        }
    }

    public function rejectSuggestion(int $index): void
    {
        if (isset($this->styleCheckSuggestions[$index])) {
            $this->styleCheckSuggestions[$index]['status'] = 'rejected';
        }
    }

    public function applyAcceptedSuggestions(): void
    {
        if (! $this->viewingDocumentId) {
            return;
        }

        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document) {
            return;
        }

        $fullPath = base_path($document->file_path);
        if (! file_exists($fullPath)) {
            return;
        }

        $content = $this->documentContent;

        // Apply accepted suggestions
        foreach ($this->styleCheckSuggestions as $suggestion) {
            if ($suggestion['status'] === 'accepted' && ! empty($suggestion['original'])) {
                $content = str_replace($suggestion['original'], $suggestion['replacement'], $content);
            }
        }

        // Save to file
        file_put_contents($fullPath, $content);

        // Update the document content in memory
        $this->documentContent = $content;

        // Clear applied suggestions
        $this->styleCheckSuggestions = array_filter($this->styleCheckSuggestions, function ($s) {
            return $s['status'] !== 'accepted';
        });
        $this->styleCheckSuggestions = array_values($this->styleCheckSuggestions);
    }

    // Milestone Methods
    public function updateMilestoneStatus(int $milestoneId, string $status): void
    {
        $milestone = ProjectMilestone::find($milestoneId);
        if ($milestone && $milestone->project_id === $this->project->id) {
            $milestone->update([
                'status' => $status,
                'completed_date' => $status === 'completed' ? now() : null,
            ]);
        }
    }

    // Stats for overview
    public function getStats(): array
    {
        return [
            'publications_total' => $this->project->publications->count(),
            'publications_published' => $this->project->publications->where('status', 'published')->count(),
            'publications_drafting' => $this->project->publications->whereIn('status', ['drafting', 'editing'])->count(),
            'events_total' => $this->project->events->count(),
            'events_upcoming' => $this->project->events->where('status', '!=', 'completed')->count(),
            'events_completed' => $this->project->events->where('status', 'completed')->count(),
            'milestones_total' => $this->project->milestones->count(),
            'milestones_completed' => $this->project->milestones->where('status', 'completed')->count(),
            'milestones_overdue' => $this->project->milestones->filter(fn ($m) => $m->isOverdue())->count(),
            'documents' => $this->project->documents->count(),
        ];
    }

    // Timeline data for visualization
    public function getTimelineData(): array
    {
        $months = [];
        $year = 2026; // REBOOT CONGRESS year

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = \Carbon\Carbon::create($year, $month, 1);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $publications = $this->project->publications
                ->filter(fn ($p) => $p->target_date && $p->target_date->month === $month && $p->target_date->year === $year);

            $events = $this->project->events
                ->filter(fn ($e) => $e->event_date && $e->event_date->month === $month && $e->event_date->year === $year);

            $months[] = [
                'month' => $month,
                'name' => $monthStart->format('M'),
                'full_name' => $monthStart->format('F'),
                'publications' => $publications->values()->toArray(),
                'events' => $events->values()->toArray(),
                'is_current' => now()->month === $month && now()->year === $year,
                'is_past' => $monthEnd->isPast(),
            ];
        }

        return $months;
    }

    public function render()
    {
        return view('livewire.projects.workspace', [
            'stats' => $this->getStats(),
            'timelineData' => $this->getTimelineData(),
        ])->title($this->project->name.' - Workspace');
    }
}
