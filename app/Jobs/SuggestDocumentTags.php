<?php

namespace App\Jobs;

use App\Models\ProjectDocument;
use App\Support\AI\AnthropicClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SuggestDocumentTags implements ShouldQueue
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
        if (! config('ai.enabled')) {
            return;
        }

        $doc = ProjectDocument::find($this->documentId);
        if (! $doc) {
            return;
        }

        // Load KB text if available
        $content = null;
        if ($doc->content_hash) {
            $kbPath = "kb/{$doc->id}-{$doc->content_hash}.txt";
            if (Storage::disk('local')->exists($kbPath)) {
                $content = Storage::disk('local')->get($kbPath);
            }
        }

        if ($content === null || trim($content) === '') {
            return;
        }

        $system = 'You are a helpful assistant that extracts concise tags from content. Return ONLY a JSON array of 3-7 short tags (strings), no extra text.';
        $user = "Content:\n".mb_substr($content, 0, 12000);

        $res = AnthropicClient::send([
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens' => 300,
        ]);

        $tags = [];
        if (! empty($res['content'][0]['text'])) {
            $json = trim($res['content'][0]['text']);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = array_values(array_unique(array_filter(array_map(function ($t) {
                    return trim((string) $t);
                }, $decoded))));
            }
        }

        if (! empty($tags)) {
            $doc->suggested_tags = $tags;
            $doc->save();
            Log::info('SuggestDocumentTags: tags generated', ['doc_id' => $doc->id, 'count' => count($tags)]);
        }
    }
}
