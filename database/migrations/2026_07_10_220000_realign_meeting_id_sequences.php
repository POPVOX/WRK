<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'meetings',
            'meeting_organization',
            'meeting_person',
            'meeting_issue',
            'meeting_user',
            'meeting_attachments',
        ] as $table) {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$table}"
            );
        }
    }

    public function down(): void
    {
        // Sequence realignment is safe and intentionally irreversible.
    }
};
