<?php

namespace App\Policies;

use App\Models\ProjectDocument;
use App\Models\User;

class ProjectDocumentPolicy
{
    public function view(User $user, ProjectDocument $document): bool
    {
        return $user->canView($document->visibility ?? 'all');
    }
}
