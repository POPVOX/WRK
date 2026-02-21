<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxProjectDocumentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'box_item_id',
        'project_id',
        'project_document_id',
        'visibility',
        'sync_status',
        'last_synced_at',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function boxItem(): BelongsTo
    {
        return $this->belongsTo(BoxItem::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectDocument(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
