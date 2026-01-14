<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('ai_analyzed_at');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null')->after('resolved_at');
            $table->text('resolution_notes')->nullable()->after('resolved_by');
            $table->string('resolution_commit')->nullable()->after('resolution_notes'); // Git commit hash
            $table->integer('resolution_effort_minutes')->nullable()->after('resolution_commit'); // Estimated effort
            $table->string('resolution_type')->nullable()->after('resolution_effort_minutes'); // fix, enhancement, wontfix, duplicate
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn([
                'resolved_at',
                'resolved_by',
                'resolution_notes',
                'resolution_commit',
                'resolution_effort_minutes',
                'resolution_type',
            ]);
        });
    }
};

