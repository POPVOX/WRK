<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalImportRun extends Model
{
    protected $fillable = [
        'source',
        'schema_version',
        'status',
        'observations_processed',
        'observations_created',
        'observations_updated',
        'manifest',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'observations_processed' => 'integer',
            'observations_created' => 'integer',
            'observations_updated' => 'integer',
            'manifest' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CongressionalStaffObservation::class, 'import_run_id');
    }
}
