<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalStaffList extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(
            CongressionalStaffProfile::class,
            'congressional_staff_list_members',
            'congressional_staff_list_id',
            'congressional_staff_profile_id'
        )->withPivot(['added_by'])->withTimestamps();
    }

    public function outreachDrafts(): HasMany
    {
        return $this->hasMany(CongressionalOutreachDraft::class, 'congressional_staff_list_id');
    }
}
