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
        Schema::create('ai_fix_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedback')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // AI Analysis
            $table->text('problem_analysis')->nullable();
            $table->json('affected_files')->nullable();
            $table->json('proposed_changes')->nullable();
            $table->text('implementation_notes')->nullable();
            $table->unsignedTinyInteger('estimated_complexity')->nullable();

            // Generated Code
            $table->longText('diff_preview')->nullable();
            $table->json('file_patches')->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'generating',
                'ready',
                'approved',
                'deployed',
                'rejected',
                'failed'
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('error_message')->nullable();

            // Deployment
            $table->string('commit_sha')->nullable();
            $table->string('branch_name')->nullable();
            $table->timestamp('deployed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['feedback_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_fix_proposals');
    }
};
