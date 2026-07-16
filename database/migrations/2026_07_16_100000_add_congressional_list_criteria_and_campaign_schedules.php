<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congressional_staff_lists', function (Blueprint $table) {
            $table->json('criteria')->nullable()->after('description');
            $table->string('selection_mode', 24)->default('selected')->after('criteria');
        });

        Schema::table('congressional_outreach_drafts', function (Blueprint $table) {
            $table->unsignedInteger('batch_size')->default(10)->after('body_text');
            $table->string('delivery_mode', 24)->default('manual')->after('batch_size');
            $table->unsignedInteger('cadence_value')->default(1)->after('delivery_mode');
            $table->string('cadence_unit', 16)->default('hour')->after('cadence_value');
            $table->string('timezone', 80)->default('America/New_York')->after('cadence_unit');
            $table->string('schedule_status', 24)->default('inactive')->index()->after('timezone');
            $table->timestamp('next_send_at')->nullable()->index()->after('schedule_status');
            $table->timestamp('last_batch_at')->nullable()->after('next_send_at');

            $table->index(['schedule_status', 'next_send_at'], 'congressional_drafts_schedule_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('congressional_outreach_drafts', function (Blueprint $table) {
            $table->dropIndex('congressional_drafts_schedule_due_index');
            $table->dropIndex('congressional_outreach_drafts_schedule_status_index');
            $table->dropIndex('congressional_outreach_drafts_next_send_at_index');
            $table->dropColumn([
                'batch_size',
                'delivery_mode',
                'cadence_value',
                'cadence_unit',
                'timezone',
                'schedule_status',
                'next_send_at',
                'last_batch_at',
            ]);
        });

        Schema::table('congressional_staff_lists', function (Blueprint $table) {
            $table->dropColumn(['criteria', 'selection_mode']);
        });
    }
};
