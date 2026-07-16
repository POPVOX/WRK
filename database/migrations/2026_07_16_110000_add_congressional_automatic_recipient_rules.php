<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congressional_outreach_drafts', function (Blueprint $table) {
            $table->boolean('auto_approve_provisional')->default(false)->after('last_batch_at');
            $table->unsignedInteger('daily_send_cap')->default(50)->after('auto_approve_provisional');
        });
    }

    public function down(): void
    {
        Schema::table('congressional_outreach_drafts', function (Blueprint $table) {
            $table->dropColumn(['auto_approve_provisional', 'daily_send_cap']);
        });
    }
};
