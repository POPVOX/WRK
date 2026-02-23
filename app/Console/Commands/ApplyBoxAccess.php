<?php

namespace App\Console\Commands;

use App\Jobs\ApplyBoxAccessOperation;
use App\Models\BoxAccessPolicy;
use App\Services\Box\BoxAccessService;
use Illuminate\Console\Command;

class ApplyBoxAccess extends Command
{
    protected $signature = 'box:access-apply
        {--operation-id= : Apply one existing operation ID}
        {--policy-id= : Create/apply operation for one policy ID}
        {--all : Create/apply operations for all active policies}
        {--now : Run synchronously instead of queueing}';

    protected $description = 'Apply WRK Box access grant changes to Box collaborations.';

    public function handle(BoxAccessService $service): int
    {
        $operationId = $this->option('operation-id');
        $policyId = $this->option('policy-id');
        $all = (bool) $this->option('all');
        $runNow = (bool) $this->option('now');

        if (! $operationId && ! $policyId && ! $all) {
            $this->error('Provide one of --operation-id, --policy-id, or --all.');

            return self::FAILURE;
        }

        if ($operationId) {
            if ($runNow) {
                $service->applyOperation((int) $operationId);
                $this->info("Applied operation #{$operationId}.");
            } else {
                ApplyBoxAccessOperation::dispatch((int) $operationId);
                $this->info("Queued operation #{$operationId}.");
            }

            return self::SUCCESS;
        }

        $policyIds = collect();
        if ($policyId) {
            $policyIds = collect([(int) $policyId]);
        } elseif ($all) {
            $policyIds = BoxAccessPolicy::query()
                ->where('active', true)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        }

        if ($policyIds->isEmpty()) {
            $this->info('No active policies to apply.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        foreach ($policyIds as $id) {
            $operation = $service->createApplyOperationForPolicy((int) $id, null, false);
            if (! $operation) {
                $skipped++;
                continue;
            }

            $created++;
            if ($runNow) {
                $service->applyOperation((int) $operation->id);
            } else {
                ApplyBoxAccessOperation::dispatch((int) $operation->id);
            }
        }

        $mode = $runNow ? 'applied' : 'queued';
        $this->info("Box access operations {$mode}: {$created}; skipped (no pending grants): {$skipped}.");

        return self::SUCCESS;
    }
}

