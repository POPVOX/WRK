<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('goal_type', 24)->default('monitor'); // monitor, prepare, coordinate
            $table->string('status', 24)->default('draft'); // draft, active, paused, completed, archived
            $table->string('trigger_type', 32)->default('cron'); // cron, deadline, event, manual
            $table->json('trigger_config')->nullable();
            $table->json('output_config')->nullable();
            $table->unsignedSmallInteger('priority')->default(50);
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['status', 'trigger_type']);
            $table->index(['priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_goals');
    }
};
