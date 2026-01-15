<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripSponsorshipDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_sponsorship_id',
        'file_path',
        'file_name',
        'file_type',
        'extracted_text',
        'file_size',
    ];

    public function sponsorship(): BelongsTo
    {
        return $this->belongsTo(TripSponsorship::class, 'trip_sponsorship_id');
    }

    public static function getFileTypeOptions(): array
    {
        return [
            'contract' => 'Contract/Agreement',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'correspondence' => 'Email/Correspondence',
            'other' => 'Other',
        ];
    }
}
