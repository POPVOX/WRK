<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'file_path',
        'file_type',
        'original_filename',
        'description',
    ];

    /**
     * File type constants.
     */
    public const TYPE_IMAGE = 'image';
    public const TYPE_PDF = 'pdf';
    public const TYPE_DOCUMENT = 'document';

    public const TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_PDF,
        self::TYPE_DOCUMENT,
    ];

    /**
     * Get the meeting this attachment belongs to.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
