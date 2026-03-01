<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('service', 32); // gmail, box, gcal, slack
            $table->longText('token_data')->nullable(); // encrypted json payload
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'service']);
            $table->index(['service', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_credentials');
    }
};
