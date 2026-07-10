<?php

namespace App\Jobs;

use App\Exceptions\MeetingExtractionException;
use App\Services\MeetingAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExtractMeetingNotes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $requestId,
        public int $userId,
        public string $notes,
        public string $notesHash,
    ) {}

    public function handle(MeetingAIService $service): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'processing',
            'notes_hash' => $this->notesHash,
        ], now()->addMinutes(15));

        try {
            $data = $service->extractMeetingData($this->notes);

            Cache::put($this->cacheKey(), [
                'status' => 'complete',
                'notes_hash' => $this->notesHash,
                'data' => $data,
            ], now()->addMinutes(15));
        } catch (MeetingExtractionException $exception) {
            $this->storeFailure($exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Queued meeting extraction failed', [
                'request_id' => $this->requestId,
                'user_id' => $this->userId,
                'exception' => $exception,
            ]);
            $this->storeFailure('AI extraction failed while processing the notes. Please retry or check Admin → Metrics.');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Queued meeting extraction job failed', [
            'request_id' => $this->requestId,
            'user_id' => $this->userId,
            'exception' => $exception,
        ]);
        $this->storeFailure('The background extraction job stopped unexpectedly. Please retry or ask an administrator to check the queue.');
    }

    public static function cacheKeyFor(string $requestId, int $userId): string
    {
        return "meeting-extraction:{$userId}:{$requestId}";
    }

    protected function cacheKey(): string
    {
        return self::cacheKeyFor($this->requestId, $this->userId);
    }

    protected function storeFailure(string $message): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'notes_hash' => $this->notesHash,
            'message' => $message,
        ], now()->addMinutes(15));
    }
}
