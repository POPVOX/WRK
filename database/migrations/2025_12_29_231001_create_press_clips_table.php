<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('press_clips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url')->unique();
            $table->string('outlet_name');
            $table->foreignId('outlet_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('journalist_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('journalist_name')->nullable();
            $table->date('published_at');
            $table->enum('clip_type', ['article', 'broadcast', 'podcast', 'opinion', 'mention', 'interview'])->default('article');
            $table->enum('sentiment', ['positive', 'neutral', 'negative', 'mixed'])->default('neutral');
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('approved');
            $table->integer('reach')->nullable();
            $table->text('summary')->nullable();
            $table->text('quotes')->nullable();
            $table->text('notes')->nullable();
            $table->enum('source', ['manual', 'web_search', 'google_alert', 'shared'])->default('manual');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('published_at');
            $table->index('status');
        });

        // Pivot: clips <-> projects
        Schema::create('press_clip_project', function (Blueprint $table) {
            $table->foreignId('press_clip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['press_clip_id', 'project_id']);
        });

        // Pivot: clips <-> issues
        Schema::create('press_clip_issue', function (Blueprint $table) {
            $table->foreignId('press_clip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->primary(['press_clip_id', 'issue_id']);
        });

        // Pivot: clips <-> staff mentioned
        Schema::create('press_clip_person', function (Blueprint $table) {
            $table->foreignId('press_clip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('users')->cascadeOnDelete();
            $table->enum('mention_type', ['quoted', 'mentioned', 'interviewed'])->default('mentioned');
            $table->primary(['press_clip_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('press_clip_person');
        Schema::dropIfExists('press_clip_issue');
        Schema::dropIfExists('press_clip_project');
        Schema::dropIfExists('press_clips');
    }
};
