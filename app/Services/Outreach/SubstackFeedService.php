<?php

namespace App\Services\Outreach;

use App\Models\OutreachSubstackConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SubstackFeedService
{
    /**
     * @return array<int,array{title:string,url:string,published_at:?string,author:?string}>
     */
    public function fetchRecentPosts(OutreachSubstackConnection $connection, int $limit = 8): array
    {
        $feedUrl = $this->resolveFeedUrl($connection);
        if ($feedUrl === '') {
            throw new RuntimeException('Set Substack publication URL or RSS feed URL first.');
        }

        $response = Http::accept('application/rss+xml, application/xml;q=0.9, */*;q=0.8')
            ->timeout(20)
            ->get($feedUrl)
            ->throw();

        $xmlBody = (string) $response->body();
        $xml = @simplexml_load_string($xmlBody);
        if ($xml === false) {
            throw new RuntimeException('Substack feed returned invalid XML.');
        }

        $items = $xml->channel->item ?? [];
        $posts = [];
        $count = 0;

        foreach ($items as $item) {
            if ($count >= max(1, min($limit, 20))) {
                break;
            }

            $published = trim((string) ($item->pubDate ?? ''));
            $posts[] = [
                'title' => trim((string) ($item->title ?? 'Untitled')),
                'url' => trim((string) ($item->link ?? '')),
                'published_at' => $published !== '' ? Carbon::parse($published)->format('M j, Y g:i A') : null,
                'author' => trim((string) ($item->author ?? '')) ?: null,
            ];
            $count++;
        }

        $connection->update([
            'status' => 'connected',
            'last_synced_at' => now(),
            'last_error' => null,
        ]);

        return $posts;
    }

    public function resolveFeedUrl(OutreachSubstackConnection $connection): string
    {
        $rss = trim((string) $connection->rss_feed_url);
        if ($rss !== '') {
            return $rss;
        }

        $publicationUrl = trim((string) $connection->publication_url);
        if ($publicationUrl === '') {
            return '';
        }

        return rtrim($publicationUrl, '/').'/feed';
    }
}

