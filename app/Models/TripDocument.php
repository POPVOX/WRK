<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TripDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'uploaded_by',
        'filename',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_path',
        'type',
        'description',
        'ai_processed',
        'ai_processed_at',
    ];

    protected $casts = [
        'ai_processed' => 'boolean',
        'ai_processed_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->storage_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'itinerary' => '📋',
            'confirmation' => '✅',
            'receipt' => '🧾',
            'invoice' => '📃',
            'boarding_pass' => '🎫',
            'visa' => '🛂',
            'insurance' => '🛡️',
            'agenda' => '📅',
            'presentation' => '📊',
            default => '📄',
        };
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public static function getTypeOptions(): array
    {
        return [
            'itinerary' => 'Itinerary',
            'confirmation' => 'Confirmation',
            'receipt' => 'Receipt',
            'invoice' => 'Invoice',
            'boarding_pass' => 'Boarding Pass',
            'visa' => 'Visa',
            'insurance' => 'Insurance',
            'agenda' => 'Agenda',
            'presentation' => 'Presentation',
            'other' => 'Other',
        ];
    }
}
