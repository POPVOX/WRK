<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrantDocument extends Model
{
    protected $fillable = [
        'grant_id',
        'title',
        'type',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'ai_extracted_data',
        'ai_processed',
        'ai_summary',
        'uploaded_by',
    ];

    protected $casts = [
        'ai_extracted_data' => 'array',
        'ai_processed' => 'boolean',
        'file_size' => 'integer',
    ];

    public const TYPES = [
        'application' => 'Grant Application',
        'agreement' => 'Grant Agreement',
        'report' => 'Report',
        'amendment' => 'Amendment',
        'other' => 'Other',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }
}
