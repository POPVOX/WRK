<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('actions')) {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('actions', 'id'), COALESCE((SELECT MAX(id) FROM actions), 0) + 1, false)"
        );
    }

    public function down(): void
    {
        // No-op: sequence repair is intentionally irreversible.
    }
};

