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
        Schema::table('meetings', function (Blueprint $table) {
            // Prep notes - preparation before the meeting (AI-generated or manual)
            $table->text('prep_notes')->nullable()->after('meeting_link_type');

            // AI summary specifically for prep analysis
            $table->json('prep_analysis')->nullable()->after('prep_notes');

            // Rename ai_summary to notes_summary for clarity (for post-meeting notes)
            // Keep ai_summary for backward compatibility, add notes_summary as new field
            $table->text('notes_summary')->nullable()->after('ai_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['prep_notes', 'prep_analysis', 'notes_summary']);
        });
    }
};
