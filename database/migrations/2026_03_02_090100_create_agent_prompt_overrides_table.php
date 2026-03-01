<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_prompt_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('override_key', 120);
            $table->json('override_value')->nullable();
            $table->string('source_layer', 32)->default('personal');
            $table->timestamps();

            $table->unique(['agent_id', 'override_key']);
            $table->index('source_layer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_prompt_overrides');
    }
};
