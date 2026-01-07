<?php

namespace App\Jobs;

use App\Models\ProjectDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchLinkContent implements ShouldQueue
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
        if (! $doc || $doc->type !== 'link' || empty($doc->url)) {
            return;
        }

        $url = $doc->url;

        // Basic safety: only allow http/https and block local/private networks
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning('FetchLinkContent: invalid URL', ['doc_id' => $doc->id, 'url' => $url]);

            return;
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            Log::warning('FetchLinkContent: blocked non-http scheme', ['doc_id' => $doc->id, 'url' => $url]);

            return;
        }

        $allow = array_values(array_filter(config('ai.safety.link.allow_domains', [])));
        $deny = array_values(array_filter(config('ai.safety.link.deny_domains', [])));

        if (! empty($deny) && in_array($host, $deny, true)) {
            Log::warning('FetchLinkContent: blocked by denylist', ['doc_id' => $doc->id, 'host' => $host]);

            return;
        }

        if (! empty($allow) && ! in_array($host, $allow, true)) {
            Log::warning('FetchLinkContent: host not in allowlist', ['doc_id' => $doc->id, 'host' => $host]);

            return;
        }

        $blockedHosts = ['localhost', '127.0.0.1', '::1'];
        $blockedPrefixes = ['10.', '192.168.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.', '169.254.'];
        if (in_array($host, $blockedHosts, true) || Str::startsWith($host, $blockedPrefixes)) {
            Log::warning('FetchLinkContent: blocked private/loopback host', ['doc_id' => $doc->id, 'host' => $host]);

            return;
        }

        // Try Google Docs export if possible
        $content = null;
        if (preg_match('#docs\.google\.com/document/d/([^/]+)/?#', $url, $m)) {
            $id = $m[1];
            $exportUrl = "https://docs.google.com/document/d/{$id}/export?format=txt";
            try {
                $res = Http::timeout(30)->get($exportUrl);
                if ($res->successful()) {
                    $content = $res->body();
                }
            } catch (\Throwable $e) {
                Log::warning('FetchLinkContent: Google Docs fetch failed', ['doc_id' => $doc->id, 'error' => $e->getMessage()]);
            }
        }

        // Fallback: attempt plain GET (may return HTML; skip if not text)
        if ($content === null) {
            try {
                $res = Http::timeout(15)->get($url);
                $contentType = strtolower($res->header('content-type', ''));

                if ($res->successful() && Str::startsWith($contentType, 'text/')) {
                    // Enforce a max read to avoid huge responses
                    $body = $res->body();
                    $maxBytes = (int) config('ai.safety.link.max_bytes', 2_000_000);
                    if (strlen($body) > $maxBytes) {
                        Log::warning('FetchLinkContent: response too large, skipping', ['doc_id' => $doc->id, 'size' => strlen($body), 'max' => $maxBytes]);
                    } else {
                        $content = $body;
                    }
                }
            } catch (\Throwable $e) {
                Log::info('FetchLinkContent: generic fetch failed', ['doc_id' => $doc->id, 'error' => $e->getMessage()]);
            }
        }

        if ($content !== null) {
            $hash = hash('sha256', $content);
            $cachePath = "links_cache/{$doc->id}-{$hash}.txt";
            Storage::disk('local')->put($cachePath, $content);

            $doc->content_hash = $hash;
            $doc->ai_summary = 'Cached link content for knowledge base';
            $doc->save();

            Log::info('FetchLinkContent: cached', ['doc_id' => $doc->id, 'hash' => $hash]);

            // Index into knowledge base
            IndexDocumentContent::dispatch($doc->id);

            // Suggest tags if AI enabled
            if (config('ai.enabled')) {
                \App\Jobs\SuggestDocumentTags::dispatch($doc->id);
            }
        }
    }
}
