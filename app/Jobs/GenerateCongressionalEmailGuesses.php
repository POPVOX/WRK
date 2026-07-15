<?php

namespace App\Jobs;

use App\Models\CongressionalOutreachDraft;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use App\Services\CongressionalDirectory\CongressionalOutreachWorkbenchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateCongressionalEmailGuesses implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 600;

    public function __construct(
        public int $draftId,
        public int $userId,
        public string $instructions,
        public string $housePattern,
        public string $senatePattern
    ) {}

    public function uniqueId(): string
    {
        return 'congressional-email-guesses-'.$this->draftId;
    }

    public function handle(
        CongressionalEmailGuessService $guesses,
        CongressionalOutreachWorkbenchService $workbench
    ): void {
        $draft = CongressionalOutreachDraft::query()->find($this->draftId);
        if (! $draft) {
            return;
        }

        $result = $guesses->generateForDraft(
            $draft,
            $this->userId,
            $this->instructions,
            $this->housePattern,
            $this->senatePattern
        );
        $workbench->refreshSnapshot($draft);
        $draft->refresh();
        $metadata = $draft->metadata ?? [];
        $metadata['email_guess_batch'] = array_merge(
            $metadata['email_guess_batch'] ?? [],
            $result,
            [
                'status' => 'completed',
                'completed_at' => now()->toIso8601String(),
            ]
        );
        $draft->update(['metadata' => $metadata]);
    }

    public function failed(?Throwable $exception): void
    {
        $draft = CongressionalOutreachDraft::query()->find($this->draftId);
        if (! $draft) {
            return;
        }

        $metadata = $draft->metadata ?? [];
        $metadata['email_guess_batch'] = array_merge(
            $metadata['email_guess_batch'] ?? [],
            [
                'status' => 'failed',
                'error' => 'The provisional email batch could not be completed. Please retry.',
                'failed_at' => now()->toIso8601String(),
            ]
        );
        $draft->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);
    }
}
