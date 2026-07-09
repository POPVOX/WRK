<?php

namespace App\Policies;

use App\Models\ProjectNote;
use App\Models\User;

class ProjectNotePolicy
{
    public function delete(User $user, ProjectNote $note): bool
    {
        return $user->isManagement() || $note->user_id === $user->id;
    }
}
