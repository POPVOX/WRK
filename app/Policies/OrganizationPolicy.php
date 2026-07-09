<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    // Organizations have no owner column; deleting one orphans its people,
    // so it is restricted to management.
    public function delete(User $user, Organization $organization): bool
    {
        return $user->isManagement();
    }
}
