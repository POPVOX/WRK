<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProfileAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'uploaded_by',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'notes',
    ];

    /**
     * Get the parent attachable model (Organization or Person).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
