<?php

namespace App\Jobs;

use App\Services\Box\BoxAccessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyBoxAccessOperation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $operationId
    ) {}

    public function handle(BoxAccessService $service): void
    {
        $service->applyOperation($this->operationId);
    }
}

