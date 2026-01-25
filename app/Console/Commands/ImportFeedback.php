<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportFeedback extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'feedback:import {file : Path to JSON export file} {--mark-fixed : Mark already-fixed items as resolved}';

    /**
     * The console command description.
     */
    protected $description = 'Import feedback from a JSON export file';

    /**
     * IDs of feedback items that have been verified as already implemented.
     * From previous code review session.
     */
    protected array $alreadyFixedIds = [
        23, // Display URLs without https:// or www.
        24, // Inline editing of organization names
        25, // Auto-add spaces to concatenated names
        26, // Calendar import filtering
        28, // Team map display Hawaii
        29, // Remove "mention" from clip types
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $json = File::get($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());
            return 1;
        }

        // Handle wrapped format: {feedback: [...]}
        $items = $data['feedback'] ?? $data;
        if (!is_array($items)) {
            $this->error('No feedback items found in JSON');
            return 1;
        }

        $imported = 0;
        $updated = 0;
        $markFixed = $this->option('mark-fixed');

        foreach ($items as $item) {
            // Map export format to database format
            $user = $item['user'] ?? null;

            $feedback = Feedback::updateOrCreate(
                ['id' => $item['id']],
                [
                    'user_id' => is_array($user) ? ($user['id'] ?? null) : ($item['user_id'] ?? null),
                    'message' => $item['message'] ?? '',
                    'feedback_type' => $item['type'] ?? $item['feedback_type'] ?? 'general',
                    'category' => $item['category'] ?? null,
                    'page_url' => $item['page_url'] ?? null,
                    'page_route' => $item['page_route'] ?? null,
                    'status' => $item['status'] ?? 'new',
                    'priority' => $item['priority'] ?? null,
                    'assigned_to' => is_array($item['assigned_to'] ?? null) ? ($item['assigned_to']['id'] ?? null) : ($item['assigned_to'] ?? null),
                    'admin_notes' => $item['admin_notes'] ?? null,
                    'screenshot_path' => $item['screenshot_path'] ?? null,
                    'browser' => $item['browser'] ?? null,
                    'browser_version' => $item['browser_version'] ?? null,
                    'os' => $item['os'] ?? null,
                    'device_type' => $item['device'] ?? $item['device_type'] ?? null,
                    'viewport_size' => $item['screen_size'] ?? $item['viewport_size'] ?? null,
                    'ai_summary' => $item['ai_analysis'] ?? $item['ai_summary'] ?? null,
                    'ai_tags' => $item['ai_tags'] ?? null,
                    'ai_recommendations' => $item['ai_recommendations'] ?? null,
                    'ai_analyzed_at' => $item['ai_analyzed_at'] ?? null,
                    'resolved_at' => $item['resolved_at'] ?? null,
                    'resolved_by' => $item['resolved_by'] ?? null,
                    'resolution_type' => $item['resolution_type'] ?? null,
                    'resolution_notes' => $item['resolution_notes'] ?? null,
                    'resolution_effort_minutes' => $item['resolution_effort_minutes'] ?? null,
                    'resolution_commit' => $item['resolution_commit'] ?? null,
                    'created_at' => $item['date'] ?? $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );

            $imported++;
            $this->line("  Imported #{$item['id']}: " . Str::limit($item['message'] ?? '', 50));

            // Mark as resolved if it's in our already-fixed list
            if ($markFixed && in_array($item['id'], $this->alreadyFixedIds) && !$feedback->resolved_at) {
                // Get a valid user ID for resolved_by
                $resolvedBy = auth()->id() ?? \App\Models\User::first()?->id;

                $feedback->update([
                    'status' => 'addressed',
                    'resolved_at' => now(),
                    'resolved_by' => $resolvedBy,
                    'resolution_type' => 'fix',
                    'resolution_notes' => 'Verified as already implemented during code review.',
                ]);
                $updated++;
                $this->info("  ✓ Marked #{$item['id']} as resolved (already implemented)");
            }
        }

        $this->info("Imported {$imported} feedback items.");
        if ($updated > 0) {
            $this->info("Marked {$updated} items as resolved (previously verified).");
        }

        return 0;
    }
}
