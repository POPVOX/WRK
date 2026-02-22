<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gmail_message_id')->nullable()->constrained('gmail_messages')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('thread_key', 191)->nullable();
            $table->string('suggestion_key', 80);
            $table->string('action_label', 140);
            $table->string('action_status', 32)->default('applied');
            $table->string('subject')->nullable();
            $table->string('counterpart_name')->nullable();
            $table->string('counterpart_email')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'thread_key']);
            $table->index(['suggestion_key', 'action_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_action_logs');
    }
};
