<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemaChatbotConversation extends Model
{
    protected $fillable = [
        'grant_id',
        'schema_id',
        'conversation_type',
        'messages',
        'status',
        'created_by',
    ];

    protected $casts = [
        'messages' => 'array',
    ];

    public const CONVERSATION_TYPES = [
        'setup' => 'Initial Setup',
        'refinement' => 'Schema Refinement',
        'question' => 'Question',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'completed' => 'Completed',
        'abandoned' => 'Abandoned',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(GrantReportingSchema::class, 'schema_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Add a message to the conversation.
     */
    public function addMessage(string $role, string $content, ?array $schemaChanges = null): void
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'role' => $role, // 'user' or 'assistant'
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
            'schema_changes' => $schemaChanges,
        ];

        $this->update(['messages' => $messages]);
    }

    /**
     * Get the last message.
     */
    public function getLastMessageAttribute(): ?array
    {
        $messages = $this->messages ?? [];

        return end($messages) ?: null;
    }

    /**
     * Get messages count.
     */
    public function getMessageCountAttribute(): int
    {
        return count($this->messages ?? []);
    }

    /**
     * Check if conversation is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mark conversation as completed.
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark conversation as abandoned.
     */
    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }

    /**
     * Get conversation history for AI context.
     */
    public function getConversationHistory(): array
    {
        return array_map(function ($message) {
            return [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }, $this->messages ?? []);
    }

    /**
     * Scope for active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for setup conversations.
     */
    public function scopeSetup($query)
    {
        return $query->where('conversation_type', 'setup');
    }
}

