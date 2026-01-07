<?php

namespace App\Jobs;

use App\Models\ProjectDocument;
use App\Services\DocumentSafety;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IndexDocumentContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public $timeout = 60;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(): void
    {
        $doc = ProjectDocument::find($this->documentId);
        if (! $doc) {
            return;
        }

        $content = null;
        $hash = $doc->content_hash;

        if ($doc->type === 'file') {
            // Only index text-like files for now
            if (in_array(strtolower($doc->file_type), ['md', 'markdown', 'txt'], true) && $doc->file_path) {
                // Support both base_path files and public storage files
                $fullBase = base_path($doc->file_path);
                $publicDiskPath = Storage::disk('public')->path($doc->file_path);
                $candidate = null;

                if (is_file($fullBase) && is_readable($fullBase)) {
                    $candidate = $fullBase;
                } elseif (is_file($publicDiskPath) && is_readable($publicDiskPath)) {
                    $candidate = $publicDiskPath;
                }

                if ($candidate) {
                    $content = file_get_contents($candidate) ?: '';
                    $hash = DocumentSafety::hashContent($content);
                }
            }
        } elseif ($doc->type === 'link') {
            // Use cached link content if available
            if ($doc->content_hash) {
                $cachePath = "links_cache/{$doc->id}-{$doc->content_hash}.txt";
                if (Storage::disk('local')->exists($cachePath)) {
                    $content = Storage::disk('local')->get($cachePath);
                    $hash = $doc->content_hash;
                }
            }
        }

        if ($content !== null) {
            $kbPath = "kb/{$doc->id}-{$hash}.txt";
            Storage::disk('local')->put($kbPath, $content);

            $doc->content_hash = $hash;
            $doc->ai_indexed = true;
            $doc->ai_summary = 'Indexed for knowledge base';
            $doc->save();

            // Prepare tags blob to make tags searchable via FTS
            $tags = is_array($doc->tags ?? null) ? $doc->tags : [];
            $tagsBlob = '';
            if (! empty($tags)) {
                $tagsBlob = "\n".implode(' ', array_map(function ($t) {
                    $t = trim((string) $t);

                    return '#'.str_replace(' ', '_', $t);
                }, $tags));
            }

            // Update FTS5 index
            try {
                DB::delete('DELETE FROM kb_index WHERE doc_id = ?', [$doc->id]);
                DB::insert('INSERT INTO kb_index (doc_id, project_id, title, body) VALUES (?, ?, ?, ?)', [
                    $doc->id,
                    $doc->project_id,
                    (string) ($doc->title ?? ''),
                    (string) ($content.$tagsBlob),
                ]);
            } catch (\Throwable $e) {
                Log::warning('IndexDocumentContent: FTS5 index update failed', [
                    'doc_id' => $doc->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('IndexDocumentContent: indexed', ['doc_id' => $doc->id, 'kb' => $kbPath]);
        }
    }
}
