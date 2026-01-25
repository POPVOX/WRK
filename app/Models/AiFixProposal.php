<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiFixProposal extends Model
{
    protected $fillable = [
        'feedback_id',
        'requested_by',
        'approved_by',
        'problem_analysis',
        'affected_files',
        'proposed_changes',
        'implementation_notes',
        'estimated_complexity',
        'diff_preview',
        'file_patches',
        'status',
        'rejection_reason',
        'error_message',
        'commit_sha',
        'branch_name',
        'deployed_at',
    ];

    protected function casts(): array
    {
        return [
            'affected_files' => 'array',
            'proposed_changes' => 'array',
            'file_patches' => 'array',
            'deployed_at' => 'datetime',
        ];
    }

    public const STATUSES = [
        'pending' => 'Pending',
        'generating' => 'Generating...',
        'ready' => 'Ready for Review',
        'approved' => 'Approved',
        'deployed' => 'Deployed',
        'rejected' => 'Rejected',
        'failed' => 'Failed',
    ];

    // Relationships

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AiFixAuditLog::class, 'proposal_id');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeDeployed($query)
    {
        return $query->where('status', 'deployed');
    }

    // Accessors

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'generating' => 'yellow',
            'ready' => 'blue',
            'approved' => 'indigo',
            'deployed' => 'green',
            'rejected' => 'red',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getComplexityLabelAttribute(): ?string
    {
        if (!$this->estimated_complexity) {
            return null;
        }

        return match (true) {
            $this->estimated_complexity <= 3 => 'Simple',
            $this->estimated_complexity <= 6 => 'Moderate',
            $this->estimated_complexity <= 8 => 'Complex',
            default => 'Very Complex',
        };
    }

    /**
     * Get formatted diff with syntax highlighting classes.
     */
    public function getFormattedDiffAttribute(): ?string
    {
        if (!$this->diff_preview) {
            return null;
        }

        $lines = explode("\n", $this->diff_preview);
        $formatted = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                $formatted[] = '<span class="text-green-400">' . e($line) . '</span>';
            } elseif (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
                $formatted[] = '<span class="text-red-400">' . e($line) . '</span>';
            } elseif (str_starts_with($line, '@@')) {
                $formatted[] = '<span class="text-cyan-400">' . e($line) . '</span>';
            } elseif (str_starts_with($line, 'diff ') || str_starts_with($line, 'index ')) {
                $formatted[] = '<span class="text-gray-500">' . e($line) . '</span>';
            } elseif (str_starts_with($line, '---') || str_starts_with($line, '+++')) {
                $formatted[] = '<span class="text-yellow-400 font-semibold">' . e($line) . '</span>';
            } else {
                $formatted[] = '<span class="text-gray-300">' . e($line) . '</span>';
            }
        }

        return implode("\n", $formatted);
    }

    /**
     * Check if proposal can be deployed.
     */
    public function canDeploy(): bool
    {
        return in_array($this->status, ['ready', 'approved']);
    }

    /**
     * Check if proposal is still being generated.
     */
    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    /**
     * Get count of files affected.
     */
    public function getFileCountAttribute(): int
    {
        return count($this->affected_files ?? []);
    }
}
