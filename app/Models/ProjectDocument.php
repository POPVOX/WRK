<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_type',
        'url',
        'mime_type',
        'file_size',
        'uploaded_by',
        'ai_indexed',
        'ai_summary',
        'content_hash',
        'last_seen_at',
        'is_archived',
        'missing_on_disk',
        'is_knowledge_base',
        'tags',
        'suggested_tags',
        'google_doc_id',
        'google_doc_type',
        'last_synced_at',
        'cached_content',
        'visibility',
        'grant_associations',
        'metric_tags',
        'document_type',
    ];

    protected $casts = [
        'ai_indexed' => 'boolean',
        'is_archived' => 'boolean',
        'missing_on_disk' => 'boolean',
        'last_seen_at' => 'datetime',
        'is_knowledge_base' => 'boolean',
        'tags' => 'array',
        'suggested_tags' => 'array',
        'last_synced_at' => 'datetime',
        'grant_associations' => 'array',
        'metric_tags' => 'array',
    ];

    public const DOCUMENT_TYPES = [
        'policy_brief' => 'Policy Brief',
        'testimony' => 'Testimony',
        'model_legislation' => 'Model Legislation',
        'report' => 'Report',
        'analysis' => 'Analysis',
        'factsheet' => 'Fact Sheet',
        'presentation' => 'Presentation',
        'publication' => 'Publication',
        'blog_post' => 'Blog Post',
        'op_ed' => 'Op-Ed',
        'other' => 'Other',
    ];

    public const TYPES = [
        'file' => 'File Upload',
        'link' => 'External Link',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function boxLink(): HasOne
    {
        return $this->hasOne(BoxProjectDocumentLink::class);
    }

    // Get the URL to access the document
    public function getAccessUrl(): ?string
    {
        if ($this->type === 'link') {
            return $this->url;
        }

        if ($this->file_path) {
            return Storage::url($this->file_path);
        }

        return null;
    }

    // Get human-readable file size
    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    // Google Docs helper methods
    public function isGoogleDoc(): bool
    {
        return ! empty($this->google_doc_id);
    }

    public function needsSync(): bool
    {
        if (! $this->isGoogleDoc()) {
            return false;
        }
        if (! $this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->addHours(24)->isPast();
    }

    // Scopes
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isManagement()) {
            return $query->whereIn('visibility', ['all', 'management']);
        }

        return $query->where('visibility', 'all');
    }

    public function scopeGoogleDocs($query)
    {
        return $query->whereNotNull('google_doc_id');
    }
}
