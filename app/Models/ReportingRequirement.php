<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingRequirement extends Model
{
    protected $fillable = [
        'grant_id',
        'source_document_id',
        'name',
        'type',
        'due_date',
        'status',
        'format_requirements',
        'template_url',
        'submitted_document_id',
        'submitted_at',
        'notes',
        'source_quote',
        'metric_id',
        'auto_calculated',
    ];

    protected $casts = [
        'due_date' => 'date',
        'submitted_at' => 'date',
        'auto_calculated' => 'boolean',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(GrantDocument::class, 'source_document_id');
    }

    public function submittedDocument(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'submitted_document_id');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'submitted' && $this->due_date->isPast();
    }

    public function isDueSoon(int $days = 14): bool
    {
        return $this->status !== 'submitted'
            && $this->due_date->isFuture()
            && $this->due_date->diffInDays(now()) <= $days;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', '!=', 'submitted')
            ->where('due_date', '>=', now())
            ->orderBy('due_date');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'submitted')
            ->where('due_date', '<', now());
    }
}
