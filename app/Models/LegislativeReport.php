<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegislativeReport extends Model
{
    protected $fillable = [
        'fiscal_year',
        'report_type',
        'report_number',
        'title',
        'enactment_date',
        'document_path',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'enactment_date' => 'date',
    ];

    public const REPORT_TYPES = [
        'house' => 'House',
        'senate' => 'Senate',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ReportingRequirement::class);
    }

    public function getPendingRequirementsCountAttribute(): int
    {
        return $this->requirements()->where('status', 'pending')->count();
    }

    public function getInProgressRequirementsCountAttribute(): int
    {
        return $this->requirements()->where('status', 'in_progress')->count();
    }

    public function getOverdueRequirementsCountAttribute(): int
    {
        return $this->requirements()
            ->where('status', '!=', 'submitted')
            ->where('due_date', '<', now())
            ->count();
    }

    public function getSubmittedRequirementsCountAttribute(): int
    {
        return $this->requirements()->where('status', 'submitted')->count();
    }

    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->report_type) . ' Report ' . $this->report_number;
    }
}

