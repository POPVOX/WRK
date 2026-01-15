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
        Schema::create('trip_travelers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->enum('role', ['lead', 'participant'])->default('participant');
            $table->boolean('calendar_events_created')->default(false);
            
            // Individual traveler notes for this trip
            $table->text('personal_notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['trip_id', 'user_id']);
            $table->index(['user_id', 'trip_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_travelers');
    }
};
