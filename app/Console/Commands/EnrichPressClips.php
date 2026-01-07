<?php

namespace App\Console\Commands;

use App\Models\PressClip;
use App\Services\UrlMetadataService;
use Illuminate\Console\Command;

class EnrichPressClips extends Command
{
    protected $signature = 'press:enrich 
                            {--all : Process all clips, not just those missing data}
                            {--limit=50 : Maximum number of clips to process}
                            {--dry-run : Show what would be updated without saving}';

    protected $description = 'Fetch metadata from press clip URLs to enrich title, summary, image, and author';

    public function handle(UrlMetadataService $metadataService): int
    {
        $this->info('🔍 Enriching press clips from URL metadata...');

        $query = PressClip::query();

        // By default, only process clips that need enrichment
        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('image_url')
                    ->orWhere('title', 'like', '%coverage by%')
                    ->orWhere('title', 'like', '%coverage%')
                    ->orWhereNull('summary');
            });
        }

        $clips = $query->limit($this->option('limit'))->get();

        if ($clips->isEmpty()) {
            $this->info('No clips need enrichment. Use --all to process all clips.');

            return 0;
        }

        $this->info("Found {$clips->count()} clips to process...");
        $this->newLine();

        $bar = $this->output->createProgressBar($clips->count());
        $bar->start();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($clips as $clip) {
            $bar->advance();

            // Skip invalid URLs
            if (empty($clip->url) || ! filter_var($clip->url, FILTER_VALIDATE_URL)) {
                $skipped++;

                continue;
            }

            // Skip certain domains that block scraping
            $blockedDomains = ['google.com', 'youtube.com', 'docs.google.com', 'drive.google.com'];
            $host = parse_url($clip->url, PHP_URL_HOST);
            if (in_array($host, $blockedDomains)) {
                $skipped++;

                continue;
            }

            try {
                $metadata = $metadataService->extractMetadata($clip->url);

                if ($this->option('dry-run')) {
                    $this->newLine();
                    $this->line("  📰 {$clip->outlet_name}");
                    $this->line("     URL: {$clip->url}");
                    $this->line('     Title: '.($metadata['title'] ?? '(none)'));
                    $this->line('     Author: '.($metadata['author'] ?? '(none)'));
                    $this->line('     Image: '.($metadata['image'] ? 'Yes' : 'No'));

                    continue;
                }

                $updateData = [];

                // Update title if we got a better one
                if (! empty($metadata['title'])) {
                    $currentTitle = $clip->title;
                    // Only update if current title looks auto-generated
                    if (
                        str_contains($currentTitle, 'coverage by') ||
                        str_contains($currentTitle, 'coverage') && strlen($currentTitle) < 60
                    ) {
                        $updateData['title'] = $this->cleanTitle($metadata['title']);
                    }
                }

                // Update image if missing
                if (empty($clip->image_url) && ! empty($metadata['image'])) {
                    $updateData['image_url'] = $metadata['image'];
                }

                // Update journalist name if missing or generic
                if (empty($clip->journalist_name) && ! empty($metadata['author'])) {
                    $updateData['journalist_name'] = $this->cleanAuthor($metadata['author']);
                }

                // Update summary if missing or very short
                if ((empty($clip->summary) || strlen($clip->summary) < 50) && ! empty($metadata['description'])) {
                    $updateData['summary'] = substr($metadata['description'], 0, 500);
                }

                if (! empty($updateData)) {
                    $clip->update($updateData);
                    $updated++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing {$clip->url}: ".$e->getMessage());
            }

            // Small delay to be respectful
            usleep(200000); // 200ms
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Enrichment complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        return 0;
    }

    /**
     * Clean up the title (remove site name suffix, etc.)
     */
    private function cleanTitle(string $title): string
    {
        // Remove common suffixes like " - Washington Post" or " | Roll Call"
        $title = preg_replace('/\s*[\|\-–—]\s*[^|\-–—]+$/', '', $title);

        // Decode HTML entities
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($title);
    }

    /**
     * Clean up author names
     */
    private function cleanAuthor(string $author): string
    {
        // Remove "By " prefix
        $author = preg_replace('/^By\s+/i', '', $author);

        // Remove email addresses
        $author = preg_replace('/\s*<[^>]+@[^>]+>\s*/', '', $author);

        // Clean up extra whitespace
        $author = preg_replace('/\s+/', ' ', $author);

        return trim($author);
    }
}
