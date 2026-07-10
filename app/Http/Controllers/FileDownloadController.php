<?php

namespace App\Http\Controllers;

use App\Models\GrantDocument;
use App\Models\MeetingAttachment;
use App\Models\ProfileAttachment;
use App\Models\ProjectDocument;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Gate;

class FileDownloadController extends Controller
{
    public function __invoke(string $type, int $id)
    {
        [$path, $name] = match ($type) {
            'project-document' => $this->projectDocument($id),
            'grant-document' => $this->grantDocument($id),
            'profile-attachment' => $this->profileAttachment($id),
            'meeting-attachment' => $this->meetingAttachment($id),
            default => abort(404),
        };

        abort_if(empty($path), 404);

        return PrivateFiles::download($path, $name);
    }

    private function projectDocument(int $id): array
    {
        $document = ProjectDocument::findOrFail($id);
        Gate::authorize('view', $document);

        return [$document->file_path, $document->title.($document->file_path ? '.'.pathinfo($document->file_path, PATHINFO_EXTENSION) : null)];
    }

    private function grantDocument(int $id): array
    {
        $document = GrantDocument::with('grant')->findOrFail($id);
        Gate::authorize('view', $document);

        return [$document->file_path, $document->title.($document->file_type ? '.'.$document->file_type : null)];
    }

    private function profileAttachment(int $id): array
    {
        $attachment = ProfileAttachment::findOrFail($id);

        return [$attachment->path, $attachment->original_filename];
    }

    private function meetingAttachment(int $id): array
    {
        $attachment = MeetingAttachment::findOrFail($id);

        return [$attachment->file_path, $attachment->original_filename];
    }
}
