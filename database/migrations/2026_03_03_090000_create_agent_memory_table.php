<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_memory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('memory_type', 32); // fact, preference, decision, relationship
            $table->json('content');
            $table->foreignId('source_message_id')->nullable()->constrained('agent_messages')->nullOnDelete();
            $table->string('visibility', 16)->default('private'); // public, private
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'visibility']);
            $table->index(['agent_id', 'memory_type']);
            $table->index('source_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_memory');
    }
};
