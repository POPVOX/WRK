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
        // Main accomplishments table
        Schema::create('accomplishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                'recognition',
                'award',
                'feedback',
                'milestone',
                'speaking',
                'media',
                'learning',
                'other',
            ])->default('other');
            $table->enum('visibility', ['personal', 'team', 'organizational'])->default('team');
            $table->date('date');
            $table->string('source')->nullable(); // e.g., "Email from Senator's office"

            // Files/attachments
            $table->string('attachment_path', 500)->nullable();

            // Attribution
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_recognition')->default(false); // true if added by someone else

            // Tagging other contributors
            $table->json('contributors')->nullable(); // array of {user_id, role}

            // Project/Grant association
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('grant_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'date']);
            $table->index('visibility');
            $table->index('type');
            $table->index('is_recognition');
        });

        // Reactions to accomplishments
        Schema::create('accomplishment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accomplishment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('reaction_type', ['celebrate', 'support', 'inspiring', 'helpful'])->default('celebrate');
            $table->text('comment')->nullable();
            $table->timestamps();

            // Each user can only react once per accomplishment
            $table->unique(['accomplishment_id', 'user_id']);
        });

        // Pre-calculated user activity stats
        Schema::create('user_activity_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');

            // Auto-tracked metrics
            $table->unsignedInteger('meetings_attended')->default(0);
            $table->unsignedInteger('meetings_organized')->default(0);
            $table->unsignedInteger('documents_authored')->default(0);
            $table->unsignedInteger('projects_owned')->default(0);
            $table->unsignedInteger('projects_contributed')->default(0);
            $table->unsignedInteger('decisions_made')->default(0);

            // Grant-related
            $table->unsignedInteger('grant_deliverables')->default(0);
            $table->unsignedInteger('grant_reports')->default(0);

            // Recognition
            $table->unsignedInteger('accomplishments_added')->default(0);
            $table->unsignedInteger('recognition_received')->default(0);
            $table->unsignedInteger('recognition_given')->default(0);

            // Calculated fields
            $table->decimal('total_impact_score', 10, 2)->default(0);

            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'period_start', 'period_end']);
            $table->index('user_id');
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_stats');
        Schema::dropIfExists('accomplishment_reactions');
        Schema::dropIfExists('accomplishments');
    }
};

