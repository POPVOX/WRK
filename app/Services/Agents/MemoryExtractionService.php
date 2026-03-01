<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\AgentMessage;
use App\Models\AgentThread;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MemoryExtractionService
{
    /**
     * @param  array<int,AgentMessage>  $messages
     * @return array<int,AgentMemory>
     */
    public function extractFromExchange(Agent $agent, AgentThread $thread, array $messages): array
    {
        if (! $this->memorySchemaReady()) {
            return [];
        }

        $stored = [];
        foreach ($messages as $message) {
            $content = trim((string) $message->content);
            if (! in_array($message->role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }

            $memoryType = $this->resolveMemoryType($message->role, $content);
            $visibility = $this->resolveVisibility($thread, $message);

            $memory = AgentMemory::query()->firstOrNew([
                'agent_id' => $agent->id,
                'source_message_id' => $message->id,
                'memory_type' => $memoryType,
            ]);

            $memory->fill([
                'content' => [
                    'text' => Str::limit($content, 1600),
                    'role' => $message->role,
                    'thread_id' => $thread->id,
                    'captured_at' => now()->toIso8601String(),
                    'summary' => Str::limit($content, 180),
                ],
                'visibility' => $visibility,
                'confidence' => $this->confidenceForType($memoryType),
            ]);

            $memory->save();
            $stored[] = $memory;
        }

        return $stored;
    }

    protected function resolveMemoryType(string $role, string $content): string
    {
        $lower = Str::lower($content);
        if ($role === 'user' && preg_match('/\b(i prefer|my preference|always|never|please remember)\b/i', $content)) {
            return 'preference';
        }

        if ($role === 'assistant' && (str_contains($lower, 'executed') || str_contains($lower, 'approved'))) {
            return 'decision';
        }

        if (preg_match('/\b(contact|stakeholder|partner|donor)\b/i', $content)) {
            return 'relationship';
        }

        return 'fact';
    }

    protected function resolveVisibility(AgentThread $thread, AgentMessage $message): string
    {
        $messageVisibility = trim((string) Arr::get($message->getAttributes(), 'visibility', ''));
        if (in_array($messageVisibility, [AgentMemory::VISIBILITY_PUBLIC, AgentMemory::VISIBILITY_PRIVATE], true)) {
            return $messageVisibility;
        }

        $threadVisibility = trim((string) Arr::get($thread->getAttributes(), 'visibility', 'private'));
        if (in_array($threadVisibility, [AgentMemory::VISIBILITY_PUBLIC, AgentMemory::VISIBILITY_PRIVATE], true)) {
            return $threadVisibility;
        }

        return AgentMemory::VISIBILITY_PRIVATE;
    }

    protected function confidenceForType(string $memoryType): float
    {
        return match ($memoryType) {
            'preference' => 0.88,
            'decision' => 0.81,
            'relationship' => 0.72,
            default => 0.67,
        };
    }

    protected function memorySchemaReady(): bool
    {
        return Schema::hasTable('agent_memory')
            && Schema::hasTable('agent_threads')
            && Schema::hasTable('agent_messages');
    }
}
