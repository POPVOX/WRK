<?php

namespace App\Policies;

use App\Models\GrantDocument;
use App\Models\User;

class GrantDocumentPolicy
{
    public function view(User $user, GrantDocument $document): bool
    {
        $visibility = $document->grant?->visibility;

        return $visibility !== null && $user->canView($visibility);
    }
}
