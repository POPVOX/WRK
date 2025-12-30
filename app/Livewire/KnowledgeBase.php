<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\KbCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KnowledgeBase extends Component
{
    public string $q = '';
    public ?int $projectId = null;
    public ?string $type = null; // 'file' | 'link' | null
    public ?string $ext = null;  // md, txt, pdf, etc.
    public ?string $tag = null;  // tag filter substring

    public array $projects = [];
    public array $results = [];
    public bool $isSearching = false;

    // Saved searches (collections)
    public array $collections = [];
    public string $newCollectionName = '';

    public function mount(): void
    {
        $this->projects = Project::orderBy('name')->get(['id', 'name'])->toArray();
        $this->loadCollections();
    }

    protected function loadCollections(): void
    {
        $userId = Auth::id();
        $this->collections = KbCollection::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get(['id', 'name', 'query', 'filters', 'updated_at'])
            ->toArray();
    }

    public function saveCollection(): void
    {
        $name = trim($this->newCollectionName);
        if ($name === '' || mb_strlen($this->q) < 2) {
            return;
        }

        $filters = [
            'projectId' => $this->projectId,
            'type' => $this->type,
            'ext' => $this->ext,
            'tag' => $this->tag,
        ];

        KbCollection::updateOrCreate(
            ['user_id' => Auth::id(), 'name' => $name],
            ['query' => $this->q, 'filters' => $filters]
        );

        $this->newCollectionName = '';
        $this->loadCollections();
    }

    public function loadCollection(int $id): void
    {
        $c = KbCollection::where('user_id', Auth::id())->find($id);
        if (!$c) {
            return;
        }
        $this->q = (string) ($c->query ?? '');
        $filters = (array) ($c->filters ?? []);
        $this->projectId = $filters['projectId'] ?? null;
        $this->type = $filters['type'] ?? null;
        $this->ext = $filters['ext'] ?? null;
        $this->tag = $filters['tag'] ?? null;

        $this->search();
    }

    public function deleteCollection(int $id): void
    {
        KbCollection::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->loadCollections();
    }

    public function search(): void
    {
        $query = trim($this->q);
        $this->results = [];
        if (mb_strlen($query) < 2) {
            return;
        }

        $this->isSearching = true;

        // FTS5 match string (basic)
        $match = $query;

        // Get candidate doc IDs + snippet from FTS, order by bm25
        $ftsRows = DB::select("
            SELECT doc_id,
                   snippet(kb_index, 3, '<mark>', '</mark>', '…', 10) AS snip,
                   bm25(kb_index) AS rank
            FROM kb_index
            WHERE kb_index MATCH ?
            ORDER BY rank ASC
            LIMIT 200
        ", [$match]);

        $ids = collect($ftsRows)->pluck('doc_id')->unique()->values();
        $snippetMap = collect($ftsRows)->keyBy('doc_id')->map(fn($r) => $r->snip)->all();

        if ($ids->isEmpty()) {
            $this->isSearching = false;
            return;
        }

        // Apply filters via Eloquent
        $docsQuery = ProjectDocument::with(['project'])
            ->whereIn('id', $ids)
            ->where('is_knowledge_base', true);

        if ($this->projectId) {
            $docsQuery->where('project_id', $this->projectId);
        }
        if ($this->type) {
            $docsQuery->where('type', $this->type);
        }
        if ($this->ext) {
            $docsQuery->where('file_type', strtolower($this->ext));
        }
        if ($this->tag && trim($this->tag) !== '') {
            $docsQuery->whereJsonContains('tags', trim($this->tag));
        }

        // Keep original FTS order by mapping id -> rank index
        $rankIndex = [];
        foreach ($ftsRows as $i => $row) {
            $rankIndex[$row->doc_id] = $i;
        }

        $docs = $docsQuery->get()->sortBy(function (ProjectDocument $d) use ($rankIndex) {
            return $rankIndex[$d->id] ?? PHP_INT_MAX;
        })->take(50);

        $this->results = $docs->map(function (ProjectDocument $d) use ($snippetMap) {
            return [
                'id' => $d->id,
                'project_id' => $d->project_id,
                'title' => $d->title,
                'project' => $d->project?->name,
                'type' => $d->type,
                'file_type' => $d->file_type,
                'url' => $d->type === 'link' ? $d->url : null,
                'snippet' => $snippetMap[$d->id] ?? null,
                'ai_indexed' => (bool) $d->ai_indexed,
                'missing' => (bool) ($d->missing_on_disk ?? false),
                'archived' => (bool) ($d->is_archived ?? false),
                'tags' => $d->tags ?? [],
            ];
        })->values()->all();

        $this->isSearching = false;
    }

    public function render()
    {
        return view('livewire.knowledge-base');
    }
}
