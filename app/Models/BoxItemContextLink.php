<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxItemContextLink extends Model
{
    use HasFactory;

    public const TYPE_PROJECT = 'project';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_FUNDER = 'funder';

    protected $fillable = [
        'box_item_id',
        'link_type',
        'link_id',
        'linked_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function boxItem(): BelongsTo
    {
        return $this->belongsTo(BoxItem::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}

