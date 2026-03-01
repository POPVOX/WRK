<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactList extends Model
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

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'contact_list_person')
            ->withPivot(['added_by'])
            ->withTimestamps()
            ->orderBy('people.name');
    }
}
