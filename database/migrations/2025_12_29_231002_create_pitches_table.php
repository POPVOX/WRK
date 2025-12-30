<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pitches', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['draft', 'sent', 'following_up', 'accepted', 'declined', 'published', 'no_response'])->default('draft');
            $table->timestamp('pitched_at')->nullable();
            $table->foreignId('journalist_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('journalist_name')->nullable();
            $table->string('journalist_email')->nullable();
            $table->foreignId('outlet_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('outlet_name')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pitched_by')->constrained('users');
            $table->json('follow_ups')->nullable();
            $table->foreignId('resulting_clip_id')->nullable()->constrained('press_clips')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('pitched_at');
        });

        // Pivot: pitches <-> issues
        Schema::create('pitch_issue', function (Blueprint $table) {
            $table->foreignId('pitch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->primary(['pitch_id', 'issue_id']);
        });

        // Pitch attachments
        Schema::create('pitch_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pitch_attachments');
        Schema::dropIfExists('pitch_issue');
        Schema::dropIfExists('pitches');
    }
};
