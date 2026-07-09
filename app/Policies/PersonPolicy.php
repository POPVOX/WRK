<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    // People have no owner column; deleting one detaches meeting history,
    // so it is restricted to management.
    public function delete(User $user, Person $person): bool
    {
        return $user->isManagement();
    }
}
