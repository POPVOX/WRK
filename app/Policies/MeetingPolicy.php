<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isManagement() || $meeting->user_id === $user->id;
    }
}
