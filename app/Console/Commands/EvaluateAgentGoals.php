<?php

namespace App\Console\Commands;

use App\Services\Agents\GoalEvaluationService;
use Illuminate\Console\Command;

class EvaluateAgentGoals extends Command
{
    protected $signature = 'agents:evaluate-goals
        {--limit=100 : Maximum number of goals to evaluate this run}';

    protected $description = 'Evaluate active agent goals and execute due triggers safely.';

    public function handle(GoalEvaluationService $goalEvaluationService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));
        $summary = $goalEvaluationService->evaluateDueGoals($limit);

        if (! ($summary['schema_ready'] ?? false)) {
            $this->warn('Agent goal tables are not ready. Run: php artisan migrate --force');

            return self::SUCCESS;
        }

        $this->info(
            'Agent goals evaluated: '
            .'evaluated='.$summary['evaluated']
            .', due='.$summary['due']
            .', triggered='.$summary['triggered']
            .', duplicates='.$summary['duplicates']
            .', skipped='.$summary['skipped']
            .', failed='.$summary['failed']
        );

        return self::SUCCESS;
    }
}
