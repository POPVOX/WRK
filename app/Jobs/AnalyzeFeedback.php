<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Support\AI\AnthropicClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeFeedback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public int $feedbackId
    ) {}

    public function handle(): void
    {
        $feedback = Feedback::find($this->feedbackId);
        if (! $feedback) {
            return;
        }

        // Skip if already analyzed
        if ($feedback->ai_analyzed_at) {
            return;
        }

        try {
            $client = app(AnthropicClient::class);

            $prompt = $this->buildPrompt($feedback);

            $response = $client->message(
                system: 'You are a product feedback analyst. Analyze user feedback and provide actionable insights. Respond only with valid JSON.',
                messages: [['role' => 'user', 'content' => $prompt]],
                maxTokens: 1000
            );

            $content = $response['content'][0]['text'] ?? '';

            // Extract JSON from response
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $jsonStr = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
                $jsonStr = $matches[0];
            } else {
                $jsonStr = $content;
            }

            $analysis = json_decode($jsonStr, true);

            if ($analysis) {
                $feedback->update([
                    'ai_summary' => $analysis['summary'] ?? null,
                    'ai_recommendations' => $analysis['recommendations'] ?? null,
                    'ai_tags' => $analysis['tags'] ?? [],
                    'priority' => $analysis['suggested_priority'] ?? null,
                    'ai_analyzed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Feedback AI analysis failed: '.$e->getMessage(), [
                'feedback_id' => $this->feedbackId,
            ]);
        }
    }

    protected function buildPrompt(Feedback $feedback): string
    {
        $context = [
            'type' => $feedback->feedback_type,
            'category' => $feedback->category,
            'message' => $feedback->message,
            'page' => $feedback->page_route ?: $feedback->page_url,
            'device' => $feedback->device_type,
            'browser' => $feedback->browser,
        ];

        return <<<PROMPT
Analyze this user feedback from our beta product:

**Feedback Type:** {$context['type']}
**Category:** {$context['category']}
**Page:** {$context['page']}
**Device:** {$context['device']} ({$context['browser']})

**User Message:**
{$context['message']}

Provide analysis in this exact JSON format:
```json
{
    "summary": "One-sentence summary of the feedback",
    "sentiment": "positive|negative|neutral|mixed",
    "suggested_priority": "low|medium|high|critical",
    "tags": ["tag1", "tag2", "tag3"],
    "recommendations": "Specific actionable recommendations for the product team (2-3 sentences)",
    "affected_area": "UI|Backend|UX|Performance|Content|Feature|Other",
    "effort_estimate": "quick-fix|medium|large|complex"
}
```
PROMPT;
    }
}
