<?php

namespace App\Jobs;

use App\Models\AiFixProposal;
use App\Services\AiCodeFixService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiFix implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AiFixProposal $proposal
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(AiCodeFixService $service): void
    {
        Log::info('Starting AI fix generation', [
            'proposal_id' => $this->proposal->id,
            'feedback_id' => $this->proposal->feedback_id,
        ]);

        try {
            // Update status to generating
            $this->proposal->update(['status' => 'generating']);

            // Generate the fix
            $service->generateFix($this->proposal);

            Log::info('AI fix generation completed', [
                'proposal_id' => $this->proposal->id,
                'status' => $this->proposal->fresh()->status,
            ]);
        } catch (\Exception $e) {
            Log::error('AI fix generation failed', [
                'proposal_id' => $this->proposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->proposal->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAiFix job failed completely', [
            'proposal_id' => $this->proposal->id,
            'error' => $exception->getMessage(),
        ]);

        $this->proposal->update([
            'status' => 'failed',
            'error_message' => 'Job failed: ' . $exception->getMessage(),
        ]);
    }
}
