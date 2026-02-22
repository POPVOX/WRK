<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agent extends Model
{
    public const SCOPE_SPECIALIST = 'specialist';
    public const SCOPE_PROJECT = 'project';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'slug',
        'scope',
        'specialty',
        'status',
        'project_id',
        'template_id',
        'created_by',
        'owner_user_id',
        'mission',
        'instructions',
        'knowledge_sources',
        'governance_tiers',
        'autonomy_mode',
        'is_persistent',
        'last_directed_at',
    ];

    protected $casts = [
        'knowledge_sources' => 'array',
        'governance_tiers' => 'array',
        'is_persistent' => 'boolean',
        'last_directed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $agent): void {
            if (empty($agent->slug)) {
                $agent->slug = Str::slug($agent->name).'-'.Str::random(6);
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class, 'template_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(AgentThread::class)->latest('updated_at');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AgentRun::class)->latest('created_at');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AgentSuggestion::class)->latest('created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isProjectScoped(): bool
    {
        return $this->scope === self::SCOPE_PROJECT;
    }
}
