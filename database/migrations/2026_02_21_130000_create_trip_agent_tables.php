<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->unique('trip_id');
        });

        Schema::create('trip_agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('trip_agent_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 32);
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('role');
        });

        Schema::create('trip_agent_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('trip_agent_conversations')->cascadeOnDelete();
            $table->foreignId('proposed_by_message_id')->nullable()->constrained('trip_agent_messages')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('summary')->nullable();
            $table->json('payload');
            $table->json('execution_log')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_agent_actions');
        Schema::dropIfExists('trip_agent_messages');
        Schema::dropIfExists('trip_agent_conversations');
    }
};
