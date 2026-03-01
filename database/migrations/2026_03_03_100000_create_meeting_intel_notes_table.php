<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_intel_notes', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64)->default('manual');
            $table->string('source_ref', 191)->nullable();
            $table->string('slack_channel_id', 64)->nullable();
            $table->string('slack_message_ts', 64)->nullable();
            $table->string('slack_thread_ts', 64)->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_label', 191)->nullable();
            $table->text('content');
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('grants')->nullOnDelete();
            $table->json('person_ids')->nullable();
            $table->json('organization_ids')->nullable();
            $table->json('funder_organization_ids')->nullable();
            $table->json('project_ids')->nullable();
            $table->json('grant_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index(['meeting_id', 'created_at']);
            $table->index(['slack_channel_id', 'created_at']);
            $table->unique(['slack_channel_id', 'slack_message_ts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_intel_notes');
    }
};

