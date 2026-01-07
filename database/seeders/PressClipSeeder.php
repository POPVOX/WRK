<?php

namespace Database\Seeders;

use App\Models\PressClip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PressClipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('POPVOX Foundation Media Hits - 2025.csv');

        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");

            return;
        }

        // Read the CSV file
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->command->error('Could not open CSV file');

            return;
        }

        // Get headers
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        // Clean up carriage returns from headers
        $headers = array_map(function ($h) {
            return str_replace(["\r", "\n"], '', $h);
        }, $headers);

        $this->command->info('Headers found: '.implode(', ', $headers));

        $imported = 0;
        $skipped = 0;
        $lineNumber = 1;

        // Get admin user for created_by
        $adminUser = User::where('is_admin', true)->first();
        $createdBy = $adminUser?->id;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to headers
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? null;
            }

            // Parse date - handle various formats
            $publishedAt = null;
            $dateStr = trim($data['Date'] ?? '');

            if (! empty($dateStr)) {
                try {
                    // Fix typo like "1/6/0205" -> "1/6/2025"
                    $dateStr = preg_replace('/\/0(\d{3})$/', '/2$1', $dateStr);

                    // Try parsing as M/D/YYYY
                    $publishedAt = Carbon::createFromFormat('n/j/Y', $dateStr);
                } catch (\Exception $e) {
                    try {
                        // Try other formats
                        $publishedAt = Carbon::parse($dateStr);
                    } catch (\Exception $e2) {
                        $this->command->warn("Line {$lineNumber}: Could not parse date '{$dateStr}'");
                    }
                }
            }

            // Skip if no URL (required field indicator)
            $url = trim($data['URL'] ?? '');
            if (empty($url)) {
                $skipped++;

                continue;
            }

            // Check if already exists
            if (PressClip::where('url', $url)->exists()) {
                $this->command->info("Skipping duplicate: {$url}");
                $skipped++;

                continue;
            }

            // Parse outlet
            $outletName = trim($data['Outlet'] ?? 'Unknown');

            // Parse journalist/byline
            $journalistName = trim($data['Byline'] ?? '');
            // Clean up text like "(op-ed)" or names with extra info
            $journalistName = preg_replace('/\s*\(.*?\)\s*$/', '', $journalistName);

            // Parse the summary/how cited - this is often multi-line
            $howCited = trim($data['How cited/blurb'] ?? '');
            // Clean up embedded quotes and newlines
            $howCited = str_replace(['""', "\r\n", "\r"], ['"', "\n", "\n"], $howCited);

            // Parse staffer mentioned
            $stafferMentioned = trim($data['Staffer mentioned'] ?? '');

            // Parse topic
            $topic = trim($data['Topic'] ?? '');

            // Determine clip type based on outlet/url
            $clipType = $this->determineClipType($outletName, $url);

            // Determine sentiment (default to neutral for news)
            $sentiment = 'neutral';

            // Build notes from topic and pitched status
            $pitched = trim($data['Pitched?'] ?? '');
            $notes = '';
            if (! empty($topic)) {
                $notes .= "Topics: {$topic}";
            }
            if (! empty($pitched)) {
                $notes .= ($notes ? "\n" : '')."Pitched: {$pitched}";
            }
            if (! empty($stafferMentioned)) {
                $notes .= ($notes ? "\n" : '')."Staff mentioned: {$stafferMentioned}";
            }

            // Create the press clip
            try {
                PressClip::create([
                    'title' => $this->extractTitle($outletName, $howCited, $journalistName),
                    'url' => $url,
                    'outlet_name' => $outletName,
                    'journalist_name' => $journalistName ?: null,
                    'published_at' => $publishedAt,
                    'clip_type' => $clipType,
                    'sentiment' => $sentiment,
                    'status' => 'approved', // Pre-approve since these are verified
                    'summary' => $this->truncateSummary($howCited),
                    'quotes' => $this->extractQuotes($howCited),
                    'notes' => $notes ?: null,
                    'source' => 'manual', // Using 'manual' as per enum constraint
                    'created_by' => $createdBy,
                ]);

                $imported++;
                $this->command->info("Imported: {$outletName} - ".($publishedAt ? $publishedAt->format('Y-m-d') : 'no date'));
            } catch (\Exception $e) {
                $this->command->error("Line {$lineNumber}: Error importing - ".$e->getMessage());
                Log::error('PressClip import error', [
                    'line' => $lineNumber,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        fclose($handle);

        $this->command->info("Import complete: {$imported} imported, {$skipped} skipped");
    }

    /**
     * Determine clip type based on outlet name and URL
     */
    private function determineClipType(string $outlet, string $url): string
    {
        $outlet = strtolower($outlet);
        $url = strtolower($url);

        // Podcasts
        if (str_contains($outlet, 'podcast') || str_contains($url, 'podcast')) {
            return 'podcast';
        }

        // TV/Broadcast
        if (str_contains($outlet, 'tv') || str_contains($outlet, 'network') && str_contains($url, 'video')) {
            return 'broadcast';
        }

        // YouTube videos
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'broadcast';
        }

        // Opinion/Substack
        if (str_contains($outlet, 'substack') || str_contains($url, 'substack') || str_contains($outlet, 'opinion')) {
            return 'opinion';
        }

        // Check URL for opinion sections
        if (str_contains($url, '/opinion/') || str_contains($url, '/op-ed')) {
            return 'opinion';
        }

        // Default to article
        return 'article';
    }

    /**
     * Extract a title from the content
     */
    private function extractTitle(string $outlet, string $howCited, string $journalist): string
    {
        // Try to extract headline from how cited
        // Often the format is: "Feature about X" or quotes from article

        // Look for a headline pattern
        if (preg_match('/^([^:]+):\s*$/', $howCited, $matches)) {
            return trim($matches[1]);
        }

        // Look for quoted headline
        if (preg_match('/["""]([^"""]+)["""]/', $howCited, $matches)) {
            $title = trim($matches[1]);
            if (strlen($title) < 150 && strlen($title) > 10) {
                return $title;
            }
        }

        // Try to get first sentence
        $firstLine = strtok($howCited, "\n");
        if ($firstLine && strlen($firstLine) < 200) {
            // Clean it up
            $title = preg_replace('/^(Feature|Interview|Story|Article|Op-ed)\s+(about|on|with)\s+/i', '', $firstLine);
            $title = trim($title, '.:');

            if (strlen($title) > 10 && strlen($title) < 150) {
                return $title;
            }
        }

        // Fallback: Generate title from outlet and author
        if (! empty($journalist)) {
            return "{$outlet} coverage by {$journalist}";
        }

        return "{$outlet} coverage";
    }

    /**
     * Truncate summary to reasonable length
     */
    private function truncateSummary(string $howCited): ?string
    {
        if (empty($howCited)) {
            return null;
        }

        // Get first 500 chars
        $summary = substr($howCited, 0, 500);

        // Try to end at a sentence
        if (strlen($howCited) > 500) {
            $lastPeriod = strrpos($summary, '.');
            if ($lastPeriod > 200) {
                $summary = substr($summary, 0, $lastPeriod + 1);
            } else {
                $summary .= '...';
            }
        }

        return $summary;
    }

    /**
     * Extract notable quotes from how cited
     */
    private function extractQuotes(string $howCited): ?string
    {
        // Find all quoted text
        preg_match_all('/["""]([^"""]+)["""]/u', $howCited, $matches);

        if (empty($matches[1])) {
            return null;
        }

        // Filter to substantial quotes (longer than 50 chars)
        $quotes = array_filter($matches[1], fn ($q) => strlen($q) > 50);

        if (empty($quotes)) {
            return null;
        }

        return implode("\n\n", array_slice($quotes, 0, 3)); // Max 3 quotes
    }
}
