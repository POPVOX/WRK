<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSuggestionSource extends Model
{
    protected $fillable = [
        'suggestion_id',
        'run_id',
        'source_type',
        'source_id',
        'source_title',
        'excerpt',
        'confidence',
        'source_url',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(AgentSuggestion::class, 'suggestion_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }
}
