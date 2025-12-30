<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectChatMessage;
use App\Support\AI\AnthropicClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SendChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;
    public int $userId;
    public string $prompt;
    public string $system;

    public $timeout = 90;

    public function __construct(int $projectId, int $userId, string $prompt, string $system)
    {
        $this->projectId = $projectId;
        $this->userId = $userId;
        $this->prompt = $prompt;
        $this->system = $system;
    }

    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (!$project) {
            Log::warning('SendChatMessage: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        $response = AnthropicClient::send([
            'system' => $this->system,
            'messages' => [
                ['role' => 'user', 'content' => $this->prompt],
            ],
            'max_tokens' => 2000,
        ]);

        $text = $response['content'][0]['text'] ?? 'No response generated.';

        ProjectChatMessage::create([
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'role' => 'assistant',
            'content' => $text,
        ]);

        Log::info('SendChatMessage: assistant message saved', [
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
        ]);
    }
}
