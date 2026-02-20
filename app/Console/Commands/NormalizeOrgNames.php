<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\OrgNameNormalizerService;
use Illuminate\Console\Command;

class NormalizeOrgNames extends Command
{
    protected $signature = 'orgs:normalize
        {--dry-run : Preview candidates without calling AI}
        {--apply : Immediately apply suggestions (skip review)}';

    protected $description = 'Use AI to normalize organization names that look like URLs or concatenated words';

    public function handle(OrgNameNormalizerService $service): int
    {
        $candidates = $service->candidates();

        if ($candidates->isEmpty()) {
            $this->info('No organizations need name normalization.');

            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} organization(s) needing normalization:");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Current Name', 'Website', 'Issue'],
                $candidates->map(fn(Organization $org) => [
                    $org->id,
                    $org->name,
                    $org->website ?? '—',
                    $org->looksLikeDomain() ? 'Domain name' : 'Condensed/no spaces',
                ])->toArray()
            );

            $this->newLine();
            $this->info('Run without --dry-run to generate AI suggestions.');

            return self::SUCCESS;
        }

        $this->info('Sending to AI for normalization...');
        $results = $service->normalize($candidates);

        if (empty($results)) {
            $this->warn('No suggestions were generated. Check logs for AI errors.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(count($results) . ' suggestion(s) generated:');
        $this->newLine();

        $rows = [];
        foreach ($results as $id => $suggestedName) {
            $org = $candidates->firstWhere('id', $id);
            $rows[] = [$id, $org?->name ?? '?', $suggestedName];
        }
        $this->table(['ID', 'Current Name', 'Suggested Name'], $rows);

        if ($this->option('apply')) {
            $this->info('Applying all suggestions...');
            foreach ($results as $id => $suggestedName) {
                Organization::where('id', $id)->update([
                    'name' => $suggestedName,
                    'suggested_name' => null,
                ]);
            }
            $this->info('Done! ' . count($results) . ' organization(s) renamed.');
        } else {
            $this->newLine();
            $this->info('Suggestions saved for review in the Organizations UI.');
            $this->info('Run with --apply to auto-apply, or review in the web interface.');
        }

        return self::SUCCESS;
    }
}
