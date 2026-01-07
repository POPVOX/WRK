<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create FTS5 table for KB content (doc_id/project_id stored as UNINDEXED metadata)
        DB::statement("
            CREATE VIRTUAL TABLE IF NOT EXISTS kb_index USING fts5(
                doc_id UNINDEXED,
                project_id UNINDEXED,
                title,
                body,
                tokenize = 'unicode61'
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS kb_index');
    }
};
