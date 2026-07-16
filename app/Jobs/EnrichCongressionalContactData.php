<?php

namespace App\Jobs;

use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class EnrichCongressionalContactData implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY = 'congressional-directory:contact-data-enrichment';

    public int $tries = 2;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 600;

    public function __construct(
        public int $userId,
        public string $instructions,
        public string $housePattern,
        public string $senatePattern
    ) {}

    public function uniqueId(): string
    {
        return self::CACHE_KEY;
    }

    public function handle(CongressionalEmailGuessService $guesses): void
    {
        $operation = Cache::get(self::CACHE_KEY, []);
        Cache::forever(self::CACHE_KEY, array_merge($operation, [
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
        ]));

        $result = $guesses->generateForAllProfiles(
            $this->userId,
            $this->instructions,
            $this->housePattern,
            $this->senatePattern
        );
        $result['corrected'] = $guesses->repairFormulaGuesses($this->userId, $this->instructions);

        Cache::forever(self::CACHE_KEY, array_merge($operation, [
            'status' => 'completed',
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ]));
    }

    public function failed(?Throwable $exception): void
    {
        $operation = Cache::get(self::CACHE_KEY, []);
        Cache::forever(self::CACHE_KEY, array_merge($operation, [
            'status' => 'failed',
            'error' => 'The contact-data enrichment could not be completed. Please retry.',
            'failed_at' => now()->toIso8601String(),
        ]));
    }
}
