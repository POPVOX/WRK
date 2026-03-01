<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_prompt_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->cascadeOnDelete();
            $table->string('layer_type', 32); // org, role, personal
            $table->longText('content');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('layer_type');
            $table->index(['agent_id', 'layer_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_prompt_layers');
    }
};
