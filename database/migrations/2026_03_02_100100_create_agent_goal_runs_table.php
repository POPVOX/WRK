<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_goal_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('agent_goals')->cascadeOnDelete();
            $table->foreignId('agent_run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
            $table->timestamp('triggered_at');
            $table->string('trigger_reason', 255)->nullable();
            $table->string('status', 24)->default('pending'); // pending, running, completed, failed
            $table->text('output_summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('idempotency_key', 64);
            $table->timestamps();

            $table->unique(['goal_id', 'idempotency_key']);
            $table->index(['status', 'triggered_at']);
            $table->index(['goal_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_goal_runs');
    }
};
