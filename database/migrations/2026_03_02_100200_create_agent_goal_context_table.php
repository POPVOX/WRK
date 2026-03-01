<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_goal_context', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('agent_goals')->cascadeOnDelete();
            $table->string('context_key', 120);
            $table->json('context_value')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['goal_id', 'context_key']);
            $table->index('goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_goal_context');
    }
};
