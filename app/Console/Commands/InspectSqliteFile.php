<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectSqliteFile extends Command
{
    protected $signature = 'db:inspect-sqlite
                            {path : Absolute path to a SQLite file}
                            {--top=25 : Maximum tables to print}';

    protected $description = 'Inspect a SQLite file and report whether it looks like a WRK database';

    protected array $wrkMarkers = [
        'users',
        'organizations',
        'people',
        'meetings',
        'projects',
        'actions',
        'project_documents',
        'feedback',
        'migrations',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $top = max(1, (int) $this->option('top'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        config([
            'database.connections.sqlite_probe' => [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite_probe');

        try {
            $tables = collect(Schema::connection('sqlite_probe')->getTableListing())
                ->map(fn ($t) => strtolower((string) $t))
                ->sort()
                ->values();
        } catch (\Throwable $e) {
            $this->error('Failed to read SQLite file: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("SQLite file: {$path}");
        $this->line('Total tables: '.$tables->count());

        $markersPresent = collect($this->wrkMarkers)->filter(fn ($t) => $tables->contains($t))->values();
        $this->line('WRK marker tables found: '.($markersPresent->isEmpty() ? 'none' : $markersPresent->join(', ')));

        if ($markersPresent->count() >= 5) {
            $this->info('This file likely contains WRK data.');
        } else {
            $this->warn('This file does not look like a WRK app database.');
        }

        $this->newLine();
        $this->line('Sample table counts:');
        $counted = 0;
        foreach ($tables as $table) {
            if ($counted >= $top) {
                break;
            }
            $count = DB::connection('sqlite_probe')->table($table)->count();
            $this->line("  {$table}: {$count}");
            $counted++;
        }

        return self::SUCCESS;
    }
}
