<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('calendar_sync_status')->nullable()->after('calendar_import_date')->index();
            $table->timestamp('calendar_sync_queued_at')->nullable()->after('calendar_sync_status');
            $table->timestamp('calendar_sync_started_at')->nullable()->after('calendar_sync_queued_at');
            $table->timestamp('calendar_sync_completed_at')->nullable()->after('calendar_sync_started_at');
            $table->timestamp('calendar_sync_failed_at')->nullable()->after('calendar_sync_completed_at');
            $table->text('calendar_sync_error')->nullable()->after('calendar_sync_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'calendar_sync_status',
                'calendar_sync_queued_at',
                'calendar_sync_started_at',
                'calendar_sync_completed_at',
                'calendar_sync_failed_at',
                'calendar_sync_error',
            ]);
        });
    }
};
