<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: use FTS5 virtual table for fast full-text search.
            DB::statement("
                CREATE VIRTUAL TABLE IF NOT EXISTS kb_index USING fts5(
                    doc_id UNINDEXED,
                    project_id UNINDEXED,
                    title,
                    body,
                    tokenize = 'unicode61'
                )
            ");

            return;
        }

        if ($driver === 'pgsql') {
            // Postgres: keep a normal table and add a GIN index on tsvector.
            DB::statement("
                CREATE TABLE IF NOT EXISTS kb_index (
                    doc_id BIGINT PRIMARY KEY,
                    project_id BIGINT NULL,
                    title TEXT,
                    body TEXT
                )
            ");

            DB::statement('CREATE INDEX IF NOT EXISTS kb_index_project_id_idx ON kb_index (project_id)');
            DB::statement("
                CREATE INDEX IF NOT EXISTS kb_index_fts_idx
                ON kb_index USING GIN (
                    to_tsvector('english', coalesce(title, '') || ' ' || coalesce(body, ''))
                )
            ");

            return;
        }

        // Fallback for unsupported drivers: allow inserts/reads without FTS features.
        DB::statement("
            CREATE TABLE IF NOT EXISTS kb_index (
                doc_id BIGINT PRIMARY KEY,
                project_id BIGINT NULL,
                title TEXT,
                body TEXT
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS kb_index');
    }
};
