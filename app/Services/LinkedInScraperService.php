<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInScraperService
{
    /**
     * Extract Open Graph metadata from a LinkedIn page.
     * 
     * LinkedIn exposes og:image and og:description meta tags on public pages.
     * This attempts to fetch that data without requiring API access.
     * 
     * @param string $linkedinUrl
     * @return array{logo_url: ?string, description: ?string, title: ?string, job_title: ?string, company: ?string}
     */
    public function extractCompanyData(string $linkedinUrl): array
    {
        try {
            // Validate it's a LinkedIn URL
            if (!$this->isValidLinkedInUrl($linkedinUrl)) {
                Log::warning("Invalid LinkedIn URL: {$linkedinUrl}");
                return $this->getEmptyResult();
            }

            // Fetch the page with a browser-like user agent
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->get($linkedinUrl);

            if (!$response->successful()) {
                Log::warning("Failed to fetch LinkedIn page: HTTP {$response->status()}");
                return $this->getEmptyResult();
            }

            $html = $response->body();
            $ogTitle = $this->extractMetaTag($html, 'og:title');

            // Parse person-specific data from og:title
            // Typical format: "Name - Title at Company | LinkedIn"
            $personData = $this->parsePersonTitle($ogTitle);

            return [
                'logo_url' => $this->extractMetaTag($html, 'og:image'),
                'description' => $this->extractMetaTag($html, 'og:description'),
                'title' => $ogTitle,
                'job_title' => $personData['job_title'],
                'company' => $personData['company'],
            ];
        } catch (\Exception $e) {
            Log::error("LinkedIn scraping error: " . $e->getMessage());
            return $this->getEmptyResult();
        }
    }

    /**
     * Parse person title and company from LinkedIn og:title.
     * Common formats:
     * - "Name - Title at Company | LinkedIn"
     * - "Name - Title | LinkedIn"
     * - "Name | LinkedIn"
     * 
     * @param string|null $ogTitle
     * @return array{job_title: ?string, company: ?string}
     */
    protected function parsePersonTitle(?string $ogTitle): array
    {
        $result = ['job_title' => null, 'company' => null];

        if (!$ogTitle) {
            return $result;
        }

        // Remove " | LinkedIn" suffix
        $cleaned = preg_replace('/\s*\|\s*LinkedIn$/i', '', $ogTitle);

        // Look for "Name - Title at Company" pattern
        if (preg_match('/^[^-]+-\s*(.+)$/i', $cleaned, $matches)) {
            $titlePart = trim($matches[1]);

            // Check for " at " separator
            if (preg_match('/^(.+?)\s+at\s+(.+)$/i', $titlePart, $atMatches)) {
                $result['job_title'] = trim($atMatches[1]);
                $result['company'] = trim($atMatches[2]);
            } else {
                // No " at ", so it's just a title or company
                $result['job_title'] = $titlePart;
            }
        }

        return $result;
    }

    /**
     * Check if the URL is a valid LinkedIn URL.
     */
    protected function isValidLinkedInUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        return str_contains($parsed['host'], 'linkedin.com');
    }

    /**
     * Extract a meta tag value from HTML.
     */
    protected function extractMetaTag(string $html, string $property): ?string
    {
        // Try og:property format
        $pattern = '/<meta[^>]*property=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        // Try content before property format
        $pattern = '/<meta[^>]*content=["\']([^"\']+)["\'][^>]*property=["\']' . preg_quote($property, '/') . '["\'][^>]*>/i';
        if (preg_match($pattern, $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        // Try name attribute instead of property
        $pattern = '/<meta[^>]*name=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }

    /**
     * Get empty result array.
     */
    protected function getEmptyResult(): array
    {
        return [
            'logo_url' => null,
            'description' => null,
            'title' => null,
            'job_title' => null,
            'company' => null,
        ];
    }
}
