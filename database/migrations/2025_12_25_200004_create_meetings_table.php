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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('meeting_date');

            // Raw capture
            $table->string('audio_path')->nullable(); // stored file path
            $table->text('transcript')->nullable();
            $table->text('raw_notes')->nullable(); // typed notes from user

            // AI-extracted fields
            $table->text('ai_summary')->nullable();
            $table->text('key_ask')->nullable();
            $table->text('commitments_made')->nullable();

            // Status tracking
            $table->string('status', 50)->default('new'); // new, action_needed, pending, complete

            $table->timestamps();

            $table->index('user_id');
            $table->index('meeting_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
