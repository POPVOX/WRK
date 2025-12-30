<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Support\AI\AnthropicClient;
use App\Services\DocumentSafety;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RunStyleCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;
    public int $documentId;
    public string $filePath;
    public string $content;
    public string $contentHash;

    public $timeout = 120;

    public function __construct(int $projectId, int $documentId, string $filePath, string $content)
    {
        $this->projectId = $projectId;
        $this->documentId = $documentId;
        $this->filePath = $filePath;
        $this->content = $content;
        $this->contentHash = DocumentSafety::hashContent($content);
    }

    public function handle(): void
    {
        $project = Project::find($this->projectId);
        $document = ProjectDocument::find($this->documentId);

        if (!$project || !$document || $document->project_id !== $project->id) {
            Log::warning('RunStyleCheck: Project/Document mismatch or missing', [
                'project_id' => $this->projectId,
                'document_id' => $this->documentId,
            ]);
            return;
        }

        $styleGuidePath = base_path('POPVOX Foundation Style Guide.md');
        $styleGuide = is_file($styleGuidePath) ? (file_get_contents($styleGuidePath) ?: '') : '';

        $system = "You are a writing assistant that strictly returns JSON.\n"
            . "You check content for compliance with the POPVOX Foundation Style Guide and suggest improvements.\n"
            . "Return a JSON object with the shape: {\"suggestions\": [{\"original\": string, \"replacement\": string, \"rule\": string, \"importance\": \"high\"|\"medium\"|\"low\"}]}.\n"
            . "Do not include any additional text.";

        $user = <<<PROMPT
STYLE_GUIDE:
{$styleGuide}

CONTENT_TO_CHECK:
{$this->content}
PROMPT;

        $response = AnthropicClient::send([
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens' => 4000,
        ]);

        $suggestions = [];
        if (!empty($response['content'][0]['text'])) {
            $json = trim($response['content'][0]['text']);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $suggestions = $decoded['suggestions'] ?? [];
            } else {
                // Fallback: try to extract JSON substring
                if (preg_match('/\{.*\}/s', $json, $m)) {
                    $fallback = json_decode($m[0], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($fallback)) {
                        $suggestions = $fallback['suggestions'] ?? [];
                    }
                }
            }
        }

        // Persist structured results to storage as JSON keyed by doc+hash
        $storePath = "style_checks/{$this->documentId}-{$this->contentHash}.json";
        Storage::disk('local')->put($storePath, json_encode([
            'document_id' => $this->documentId,
            'project_id' => $this->projectId,
            'file_path' => $this->filePath,
            'content_hash' => $this->contentHash,
            'suggestions' => $suggestions,
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        // Update document flags (cache key fields)
        $document->content_hash = $this->contentHash;
        $document->ai_indexed = true;
        $document->ai_summary = count($suggestions) . ' suggestion(s) ready';
        $document->save();

        Log::info('RunStyleCheck complete', [
            'project_id' => $this->projectId,
            'document_id' => $this->documentId,
            'suggestions' => count($suggestions),
            'content_hash' => $this->contentHash,
        ]);
    }
}
