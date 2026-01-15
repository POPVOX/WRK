<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_events', function (Blueprint $table) {
            $table->foreignId('project_event_id')->nullable()->after('meeting_id')
                ->constrained('project_events')->nullOnDelete();
            $table->boolean('ai_extracted')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_events', function (Blueprint $table) {
            $table->dropForeign(['project_event_id']);
            $table->dropColumn(['project_event_id', 'ai_extracted']);
        });
    }
};
