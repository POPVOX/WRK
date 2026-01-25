<?php

namespace App\Services;

use App\Models\AiFixProposal;
use App\Models\Feedback;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiCodeFixService
{
    protected string $projectPath;

    public function __construct()
    {
        $this->projectPath = base_path();
    }

    /**
     * Analyze feedback and identify potentially affected files.
     */
    public function analyzeIssue(Feedback $feedback): array
    {
        $systemPrompt = <<<PROMPT
You are an expert Laravel developer analyzing user feedback to identify what needs to be fixed in the codebase.

Based on the feedback message, page URL, and route, determine:
1. What the issue is
2. What types of files might be affected (controllers, models, views, etc.)
3. Suggested search patterns to find relevant code

Respond with JSON only:
```json
{
    "problem_summary": "Brief description of the issue",
    "issue_type": "bug|feature|ui|performance|logic",
    "estimated_complexity": 1-10,
    "likely_file_types": ["controller", "view", "model", "livewire", "job"],
    "search_patterns": ["pattern1", "pattern2"],
    "suggested_approach": "Brief approach to fix"
}
```
PROMPT;

        $userMessage = $this->buildAnalysisPrompt($feedback);

        try {
            $response = AnthropicClient::send([
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $userMessage]],
                'max_tokens' => 1500,
            ]);

            if (isset($response['error'])) {
                throw new \Exception('API Error: ' . ($response['body'] ?? 'Unknown error'));
            }

            $content = $response['content'][0]['text'] ?? '';
            $analysis = $this->parseJsonResponse($content);

            return [
                'success' => true,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find relevant files in the codebase based on search patterns.
     */
    public function findRelevantFiles(Feedback $feedback, array $searchPatterns): array
    {
        $relevantFiles = [];

        // Extract route name from feedback
        $routeParts = [];
        if ($feedback->page_route) {
            $routeParts = explode('.', $feedback->page_route);
        }

        // Search in common locations
        $searchDirs = [
            'app/Livewire',
            'app/Http/Controllers',
            'app/Models',
            'resources/views/livewire',
            'resources/views',
        ];

        foreach ($searchPatterns as $pattern) {
            foreach ($searchDirs as $dir) {
                $fullPath = $this->projectPath . '/' . $dir;
                if (!File::isDirectory($fullPath)) {
                    continue;
                }

                // Use grep to find files matching the pattern
                $cmd = sprintf(
                    'grep -rl %s %s 2>/dev/null | head -10',
                    escapeshellarg($pattern),
                    escapeshellarg($fullPath)
                );

                $output = shell_exec($cmd);
                if ($output) {
                    $files = array_filter(explode("\n", trim($output)));
                    $relevantFiles = array_merge($relevantFiles, $files);
                }
            }
        }

        // Dedupe and limit
        $relevantFiles = array_unique($relevantFiles);
        $relevantFiles = array_slice($relevantFiles, 0, 10);

        // Convert to relative paths
        return array_map(function ($file) {
            return str_replace($this->projectPath . '/', '', $file);
        }, $relevantFiles);
    }

    /**
     * Generate code fix proposals for the identified files.
     */
    public function generateFix(AiFixProposal $proposal): void
    {
        $feedback = $proposal->feedback;

        // First, analyze the issue
        $analysis = $this->analyzeIssue($feedback);

        if (!$analysis['success']) {
            $proposal->update([
                'status' => 'failed',
                'error_message' => $analysis['error'] ?? 'Analysis failed',
            ]);
            return;
        }

        $analysisData = $analysis['analysis'];

        // Update proposal with analysis
        $proposal->update([
            'problem_analysis' => $analysisData['problem_summary'] ?? null,
            'estimated_complexity' => $analysisData['estimated_complexity'] ?? null,
            'implementation_notes' => $analysisData['suggested_approach'] ?? null,
        ]);

        // Find relevant files
        $searchPatterns = $analysisData['search_patterns'] ?? [];
        if (empty($searchPatterns)) {
            // Generate some default patterns from the feedback
            $searchPatterns = $this->generateDefaultPatterns($feedback);
        }

        $files = $this->findRelevantFiles($feedback, $searchPatterns);

        if (empty($files)) {
            $proposal->update([
                'status' => 'failed',
                'error_message' => 'Could not find relevant files to fix',
            ]);
            return;
        }

        $proposal->update(['affected_files' => $files]);

        // Read file contents and generate fixes
        $fileContents = [];
        foreach (array_slice($files, 0, 5) as $file) {
            $fullPath = $this->projectPath . '/' . $file;
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                // Limit to first 500 lines to avoid token limits
                $lines = explode("\n", $content);
                $fileContents[$file] = implode("\n", array_slice($lines, 0, 500));
            }
        }

        if (empty($fileContents)) {
            $proposal->update([
                'status' => 'failed',
                'error_message' => 'Could not read file contents',
            ]);
            return;
        }

        // Generate the actual fix
        $fixResult = $this->generateCodeChanges($feedback, $analysisData, $fileContents);

        if (!$fixResult['success']) {
            $proposal->update([
                'status' => 'failed',
                'error_message' => $fixResult['error'] ?? 'Fix generation failed',
            ]);
            return;
        }

        // Update proposal with generated fixes
        $proposal->update([
            'proposed_changes' => $fixResult['changes'] ?? [],
            'diff_preview' => $fixResult['diff'] ?? '',
            'file_patches' => $fixResult['patches'] ?? [],
            'status' => 'ready',
        ]);
    }

    /**
     * Generate actual code changes using AI.
     */
    protected function generateCodeChanges(Feedback $feedback, array $analysis, array $fileContents): array
    {
        $systemPrompt = <<<PROMPT
You are an expert Laravel developer tasked with fixing a bug or implementing a feature request.

Given the user feedback and relevant code files, generate specific code changes to fix the issue.

IMPORTANT RULES:
1. Only modify what's necessary to fix the issue
2. Maintain the existing code style
3. Don't break existing functionality
4. Be conservative - small, targeted changes are preferred

Respond with JSON only:
```json
{
    "changes": [
        {
            "file": "path/to/file.php",
            "description": "What this change does",
            "original": "exact original code to replace",
            "replacement": "new code to insert"
        }
    ],
    "explanation": "Brief explanation of the fix",
    "testing_notes": "How to verify the fix works"
}
```
PROMPT;

        $userMessage = $this->buildFixPrompt($feedback, $analysis, $fileContents);

        try {
            $response = AnthropicClient::send([
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $userMessage]],
                'max_tokens' => 4000,
            ]);

            if (isset($response['error'])) {
                throw new \Exception('API Error: ' . ($response['body'] ?? 'Unknown error'));
            }

            $content = $response['content'][0]['text'] ?? '';
            $fixData = $this->parseJsonResponse($content);

            if (empty($fixData['changes'])) {
                return [
                    'success' => false,
                    'error' => 'No changes generated',
                ];
            }

            // Generate unified diff from changes
            $diff = $this->generateUnifiedDiff($fixData['changes']);

            return [
                'success' => true,
                'changes' => $fixData['changes'],
                'diff' => $diff,
                'patches' => $fixData['changes'],
                'explanation' => $fixData['explanation'] ?? '',
                'testing_notes' => $fixData['testing_notes'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('AI fix generation failed', [
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the analysis prompt for feedback.
     */
    protected function buildAnalysisPrompt(Feedback $feedback): string
    {
        $prompt = "Analyze this user feedback to identify what needs to be fixed:\n\n";
        $prompt .= "**Feedback Type:** {$feedback->feedback_type}\n";
        $prompt .= "**Message:** {$feedback->message}\n";
        $prompt .= "**Page URL:** {$feedback->page_url}\n";
        $prompt .= "**Route:** {$feedback->page_route}\n";

        if ($feedback->ai_summary) {
            $prompt .= "**AI Summary:** {$feedback->ai_summary}\n";
        }

        if ($feedback->ai_recommendations) {
            $prompt .= "**AI Recommendations:** {$feedback->ai_recommendations}\n";
        }

        return $prompt;
    }

    /**
     * Build the fix generation prompt.
     */
    protected function buildFixPrompt(Feedback $feedback, array $analysis, array $fileContents): string
    {
        $prompt = "## Issue to Fix\n\n";
        $prompt .= "**Problem:** " . ($analysis['problem_summary'] ?? $feedback->message) . "\n";
        $prompt .= "**User Feedback:** {$feedback->message}\n";
        $prompt .= "**Page:** {$feedback->page_url}\n";
        $prompt .= "**Route:** {$feedback->page_route}\n\n";

        if (!empty($analysis['suggested_approach'])) {
            $prompt .= "**Suggested Approach:** {$analysis['suggested_approach']}\n\n";
        }

        $prompt .= "## Relevant Files\n\n";

        foreach ($fileContents as $file => $content) {
            $prompt .= "### {$file}\n```php\n{$content}\n```\n\n";
        }

        $prompt .= "Generate the minimal code changes needed to fix this issue.";

        return $prompt;
    }

    /**
     * Generate default search patterns from feedback.
     */
    protected function generateDefaultPatterns(Feedback $feedback): array
    {
        $patterns = [];

        // Extract from route
        if ($feedback->page_route) {
            $parts = explode('.', $feedback->page_route);
            foreach ($parts as $part) {
                if (strlen($part) > 2) {
                    $patterns[] = Str::studly($part);
                    $patterns[] = Str::kebab($part);
                }
            }
        }

        // Extract keywords from message
        $words = preg_split('/\s+/', $feedback->message);
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z]/', '', $word);
            if (strlen($word) > 4 && !in_array(strtolower($word), ['should', 'would', 'could', 'there', 'their', 'about', 'going', 'being'])) {
                $patterns[] = $word;
            }
        }

        return array_unique(array_slice($patterns, 0, 5));
    }

    /**
     * Generate a unified diff from changes.
     */
    protected function generateUnifiedDiff(array $changes): string
    {
        $diff = '';

        foreach ($changes as $change) {
            $file = $change['file'] ?? 'unknown';
            $original = $change['original'] ?? '';
            $replacement = $change['replacement'] ?? '';

            $diff .= "diff --git a/{$file} b/{$file}\n";
            $diff .= "--- a/{$file}\n";
            $diff .= "+++ b/{$file}\n";
            $diff .= "@@ -1,1 +1,1 @@\n";

            // Format original lines
            $origLines = explode("\n", $original);
            foreach ($origLines as $line) {
                $diff .= "-{$line}\n";
            }

            // Format replacement lines
            $repLines = explode("\n", $replacement);
            foreach ($repLines as $line) {
                $diff .= "+{$line}\n";
            }

            $diff .= "\n";
        }

        return $diff;
    }

    /**
     * Parse JSON from AI response (handles code blocks).
     */
    protected function parseJsonResponse(string $content): array
    {
        // Try to extract JSON from code blocks
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $jsonStr = $content;
        }

        $result = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI JSON response', [
                'error' => json_last_error_msg(),
                'content' => substr($content, 0, 500),
            ]);
            return [];
        }

        return $result;
    }
}
