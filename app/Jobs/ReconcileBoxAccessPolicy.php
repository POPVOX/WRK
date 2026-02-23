<?php

namespace App\Jobs;

use App\Services\Box\BoxAccessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcileBoxAccessPolicy implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $policyId
    ) {}

    public function handle(BoxAccessService $service): void
    {
        $service->reconcilePolicy($this->policyId);
    }
}

