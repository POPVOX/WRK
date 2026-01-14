<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schema_chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained()->onDelete('cascade');
            $table->foreignId('schema_id')->nullable()->constrained('grant_reporting_schemas')->onDelete('set null');
            $table->enum('conversation_type', ['setup', 'refinement', 'question'])->default('setup');
            $table->json('messages'); // Array of {role, content, timestamp, schema_changes}
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['grant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schema_chatbot_conversations');
    }
};

