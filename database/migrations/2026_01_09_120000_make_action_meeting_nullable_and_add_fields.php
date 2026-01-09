<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            // Make meeting_id nullable to support standalone tasks
            $table->foreignId('meeting_id')->nullable()->change();

            // Add project_id for tasks linked directly to projects
            $table->foreignId('project_id')->nullable()->after('meeting_id')->constrained()->onDelete('set null');

            // Add source to track where the task came from
            $table->string('source')->default('manual')->after('status'); // manual, meeting, ai_suggested, calendar

            // Add notes field
            $table->text('notes')->nullable()->after('description');

            // Add title for clearer display of standalone tasks
            $table->string('title')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'source', 'notes', 'title']);
        });
    }
};
