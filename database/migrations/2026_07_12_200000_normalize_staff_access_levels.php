<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('access_level', 'team')->update(['access_level' => 'staff']);
        DB::table('users')->where('is_admin', true)->update(['access_level' => 'admin']);
    }

    public function down(): void
    {
        // Access-level normalization is intentionally irreversible.
    }
};
