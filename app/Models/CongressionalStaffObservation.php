<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongressionalStaffObservation extends Model
{
    protected $fillable = [
        'import_run_id',
        'profile_id',
        'office_id',
        'position_id',
        'observation_id',
        'source_record_hash',
        'chamber',
        'name_raw',
        'identity_hint',
        'office_raw',
        'office_code',
        'office_type',
        'title_raw',
        'period_label',
        'period_start',
        'period_end',
        'active_in_latest_report',
        'source_data',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'active_in_latest_report' => 'boolean',
            'source_data' => 'array',
            'evidence' => 'array',
        ];
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(CongressionalImportRun::class, 'import_run_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class, 'profile_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(CongressionalOffice::class, 'office_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(CongressionalPosition::class, 'position_id');
    }
}
