<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE press_clips ALTER COLUMN url TYPE VARCHAR(2048)');
        DB::statement('ALTER TABLE press_clips ALTER COLUMN image_url TYPE VARCHAR(2048)');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE press_clips ALTER COLUMN url TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE press_clips ALTER COLUMN image_url TYPE VARCHAR(255)');
    }
};
