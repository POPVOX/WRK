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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Page context
            $table->string('page_url', 2048);
            $table->string('page_title')->nullable();
            $table->string('page_route')->nullable();

            // Feedback content
            $table->string('feedback_type')->default('general'); // bug, suggestion, compliment, question, general
            $table->string('category')->nullable(); // UI, Performance, Feature, Content, Other
            $table->text('message');
            $table->string('screenshot_path')->nullable();

            // Browser/device metadata
            $table->string('user_agent', 1024)->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('screen_resolution')->nullable();
            $table->string('viewport_size')->nullable();

            // Status tracking
            $table->string('status')->default('new'); // new, reviewed, in_progress, addressed, dismissed
            $table->string('priority')->nullable(); // low, medium, high, critical
            $table->text('admin_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // AI analysis
            $table->text('ai_summary')->nullable();
            $table->text('ai_recommendations')->nullable();
            $table->json('ai_tags')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('feedback_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
