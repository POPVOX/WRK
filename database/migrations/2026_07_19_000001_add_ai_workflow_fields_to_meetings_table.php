<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->string('meeting_type', 50)->default('stakeholder')->after('meeting_link_type');
            $table->text('ai_focus')->nullable()->after('meeting_type');
            $table->timestamp('ai_generated_at')->nullable()->after('commitments_made');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropColumn(['meeting_type', 'ai_focus', 'ai_generated_at']);
        });
    }
};
