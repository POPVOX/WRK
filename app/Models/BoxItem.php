<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'box_item_id',
        'box_item_type',
        'name',
        'parent_box_folder_id',
        'path_display',
        'etag',
        'sha1',
        'size',
        'owned_by_login',
        'modified_at',
        'trashed_at',
        'permissions',
        'raw_payload',
        'last_synced_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'raw_payload' => 'array',
        'modified_at' => 'datetime',
        'trashed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function scopeFiles($query)
    {
        return $query->where('box_item_type', 'file');
    }

    public function scopeFolders($query)
    {
        return $query->where('box_item_type', 'folder');
    }

    public function projectDocumentLinks(): HasMany
    {
        return $this->hasMany(BoxProjectDocumentLink::class);
    }
}
