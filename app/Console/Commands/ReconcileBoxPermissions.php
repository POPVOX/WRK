<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileBoxAccessPolicy;
use App\Models\BoxAccessPolicy;
use App\Services\Box\BoxAccessService;
use Illuminate\Console\Command;

class ReconcileBoxPermissions extends Command
{
    protected $signature = 'box:reconcile-permissions
        {--policy-id= : Reconcile one policy ID}
        {--full : Reconcile all active policies}
        {--now : Run synchronously instead of queueing}';

    protected $description = 'Reconcile WRK Box access grants with actual Box collaborations.';

    public function handle(BoxAccessService $service): int
    {
        $policyId = $this->option('policy-id');
        $full = (bool) $this->option('full');
        $runNow = (bool) $this->option('now');

        if (! $policyId && ! $full) {
            $this->error('Provide --policy-id or --full.');

            return self::FAILURE;
        }

        $policyIds = $policyId
            ? collect([(int) $policyId])
            : BoxAccessPolicy::query()
                ->where('active', true)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        if ($policyIds->isEmpty()) {
            $this->info('No active policies to reconcile.');

            return self::SUCCESS;
        }

        $processed = 0;
        foreach ($policyIds as $id) {
            if ($runNow) {
                $counts = $service->reconcilePolicy((int) $id);
                $this->line("Policy {$id}: checked {$counts['checked']}, matched {$counts['matched']}, drifted {$counts['drifted']}.");
            } else {
                ReconcileBoxAccessPolicy::dispatch((int) $id);
            }

            $processed++;
        }

        if ($runNow) {
            $this->info("Reconcile complete for {$processed} policy/policies.");
        } else {
            $this->info("Queued reconcile jobs for {$processed} policy/policies.");
        }

        return self::SUCCESS;
    }
}

