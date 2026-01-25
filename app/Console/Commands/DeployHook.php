<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployHook extends Command
{
    protected $signature = 'deploy:hook 
                            {--previous-sha= : Previous deployment commit SHA}';

    protected $description = 'Post-deployment hook - runs migrations, clears caches, processes feedback';

    public function handle(): int
    {
        $previousSha = $this->option('previous-sha');

        $this->info('🚀 Running post-deployment tasks...');
        $this->newLine();

        // 1. Run migrations
        $this->task('Running migrations', function () {
            return $this->callSilently('migrate', ['--force' => true]) === 0;
        });

        // 2. Clear caches
        $this->task('Clearing caches', function () {
            $this->callSilently('view:clear');
            $this->callSilently('config:clear');
            $this->callSilently('route:clear');
            return true;
        });

        // 3. Cache config and routes for production
        $this->task('Building production caches', function () {
            $this->callSilently('config:cache');
            $this->callSilently('route:cache');
            $this->callSilently('view:cache');
            return true;
        });

        // 4. Process feedback resolutions from commits
        if ($previousSha) {
            $this->task('Auto-resolving feedback from commits', function () use ($previousSha) {
                return $this->callSilently('feedback:process-deploy', [
                    '--since' => $previousSha,
                ]) === 0;
            });
        } else {
            $this->task('Auto-resolving feedback from recent commits', function () {
                return $this->callSilently('feedback:process-deploy', [
                    '--count' => 20,
                ]) === 0;
            });
        }

        // 5. Restart queue workers
        $this->task('Restarting queue workers', function () {
            $this->callSilently('queue:restart');
            return true;
        });

        $this->newLine();
        $this->info('✅ Deployment complete!');

        return Command::SUCCESS;
    }

    /**
     * Run a task with visual feedback.
     */
    protected function task(string $title, callable $task): void
    {
        $this->output->write("  <comment>{$title}...</comment> ");

        $result = $task();

        if ($result) {
            $this->output->writeln('<info>✓</info>');
        } else {
            $this->output->writeln('<error>✗</error>');
        }
    }
}
