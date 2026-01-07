<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlMetadataService
{
    /**
     * Extract metadata from a URL using Open Graph and meta tags
     */
    public function extractMetadata(string $url): array
    {
        $result = [
            'title' => null,
            'description' => null,
            'image' => null,
            'author' => null,
            'site_name' => null,
            'published_date' => null,
        ];

        try {
            // Make request with browser-like headers
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("Failed to fetch URL: {$url} - Status: {$response->status()}");

                return $result;
            }

            $html = $response->body();

            // Parse Open Graph tags
            $result['title'] = $this->extractMetaContent($html, 'og:title')
                ?? $this->extractMetaContent($html, 'twitter:title')
                ?? $this->extractTitleTag($html);

            $result['description'] = $this->extractMetaContent($html, 'og:description')
                ?? $this->extractMetaContent($html, 'twitter:description')
                ?? $this->extractMetaContent($html, 'description');

            $result['image'] = $this->extractMetaContent($html, 'og:image')
                ?? $this->extractMetaContent($html, 'twitter:image');

            $result['author'] = $this->extractMetaContent($html, 'author')
                ?? $this->extractMetaContent($html, 'article:author')
                ?? $this->extractMetaContent($html, 'dc.creator')
                ?? $this->extractAuthorFromSchema($html)
                ?? $this->extractAuthorFromByline($html);

            $result['site_name'] = $this->extractMetaContent($html, 'og:site_name')
                ?? $this->extractSiteName($html);

            $result['published_date'] = $this->extractMetaContent($html, 'article:published_time')
                ?? $this->extractMetaContent($html, 'datePublished')
                ?? $this->extractMetaContent($html, 'pubdate');

            // Clean up values
            $result = array_map(function ($value) {
                if ($value === null) {
                    return null;
                }

                return trim(html_entity_decode(strip_tags($value)));
            }, $result);

            // Make image URL absolute if relative
            if ($result['image'] && ! str_starts_with($result['image'], 'http')) {
                $parsedUrl = parse_url($url);
                $baseUrl = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
                $result['image'] = $baseUrl.'/'.ltrim($result['image'], '/');
            }

        } catch (\Exception $e) {
            Log::error("Error extracting metadata from {$url}: ".$e->getMessage());
        }

        return $result;
    }

    /**
     * Extract meta content by property or name
     */
    private function extractMetaContent(string $html, string $name): ?string
    {
        // Try property attribute first (Open Graph)
        if (preg_match('/<meta[^>]+property=["\']'.preg_quote($name, '/').'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try with content before property
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']'.preg_quote($name, '/').'["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try name attribute
        if (preg_match('/<meta[^>]+name=["\']'.preg_quote($name, '/').'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try with content before name
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']'.preg_quote($name, '/').'["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract title from title tag
     */
    private function extractTitleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract author from JSON-LD schema
     */
    private function extractAuthorFromSchema(string $html): ?string
    {
        // Try to find JSON-LD script
        if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>([^<]+)<\/script>/i', $html, $matches)) {
            $json = @json_decode($matches[1], true);
            if ($json) {
                // Check for author in various formats
                $author = $json['author'] ?? $json['creator'] ?? null;
                if (is_array($author)) {
                    if (isset($author['name'])) {
                        return $author['name'];
                    }
                    if (isset($author[0]['name'])) {
                        return $author[0]['name'];
                    }
                    if (isset($author[0]) && is_string($author[0])) {
                        return implode(', ', $author);
                    }
                }
                if (is_string($author)) {
                    return $author;
                }
            }
        }

        return null;
    }

    /**
     * Extract author from common byline patterns
     */
    private function extractAuthorFromByline(string $html): ?string
    {
        // Common byline patterns
        $patterns = [
            '/<[^>]+class=["\'][^"\']*\bbyline\b[^"\']*["\'][^>]*>.*?<a[^>]*>([^<]+)<\/a>/is',
            '/<[^>]+class=["\'][^"\']*\bauthor[^"\']*["\'][^>]*>([^<]+)</is',
            '/<[^>]+rel=["\']author["\'][^>]*>([^<]+)</is',
            '/By\s+<a[^>]*>([^<]+)<\/a>/i',
            '/By\s+([A-Z][a-z]+\s+[A-Z][a-zA-Z\-]+)/m',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $author = trim(strip_tags($matches[1]));
                if (strlen($author) > 3 && strlen($author) < 100) {
                    return $author;
                }
            }
        }

        return null;
    }

    /**
     * Extract site name from URL if not found in meta
     */
    private function extractSiteName(string $html): ?string
    {
        return null; // Fallback handled elsewhere
    }
}
