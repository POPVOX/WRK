<?php

namespace App\Jobs;

use App\Models\CongressionalOutreachDraft;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BuildCongressionalOutreachDraftSnapshot implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 600;

    public function __construct(public int $draftId) {}

    public function uniqueId(): string
    {
        return 'congressional-outreach-draft-'.$this->draftId;
    }

    public function handle(CongressionalOutreachWorkbenchService $workbench): void
    {
        $draft = CongressionalOutreachDraft::query()->find($this->draftId);

        if (! $draft) {
            return;
        }

        $workbench->refreshSnapshot($draft);
    }

    public function failed(?Throwable $exception): void
    {
        $draft = CongressionalOutreachDraft::query()->find($this->draftId);
        if (! $draft) {
            return;
        }

        $metadata = $draft->metadata ?? [];
        $metadata['snapshot_error'] = 'The recipient snapshot could not be built. Please retry.';
        $metadata['snapshot_failed_at'] = now()->toIso8601String();

        $draft->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);
    }
}
