<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('staffer_id')->nullable()->after('owner_user_id')->constrained('users')->nullOnDelete();
            $table->index('staffer_id');
        });

        DB::table('agents')
            ->whereNull('staffer_id')
            ->update([
                'staffer_id' => DB::raw('COALESCE(owner_user_id, created_by)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staffer_id');
        });
    }
};
