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
        Schema::create('trip_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('type', [
                'flight',
                'train',
                'bus',
                'rental_car',
                'rideshare',
                'ferry',
                'other_transport'
            ]);
            
            // Common fields
            $table->string('carrier')->nullable(); // Airline, Amtrak, etc.
            $table->string('carrier_code')->nullable(); // UA, AA, etc.
            $table->string('segment_number')->nullable(); // Flight number, train number
            $table->string('confirmation_number')->nullable();
            
            // Departure
            $table->string('departure_location'); // Airport code or city
            $table->string('departure_city')->nullable();
            $table->datetime('departure_datetime');
            $table->string('departure_terminal')->nullable();
            $table->string('departure_gate')->nullable();
            
            // Arrival
            $table->string('arrival_location');
            $table->string('arrival_city')->nullable();
            $table->datetime('arrival_datetime');
            $table->string('arrival_terminal')->nullable();
            
            // Flight-specific
            $table->string('aircraft_type')->nullable();
            $table->string('seat_assignment')->nullable();
            $table->enum('cabin_class', ['economy', 'premium_economy', 'business', 'first'])->nullable();
            $table->integer('distance_miles')->nullable();
            
            // Cost
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Status
            $table->enum('status', ['scheduled', 'confirmed', 'checked_in', 'completed', 'cancelled', 'delayed'])->default('scheduled');
            
            // Booking info
            $table->string('booking_reference')->nullable();
            $table->string('ticket_number')->nullable();
            $table->text('notes')->nullable();
            
            // For AI extraction tracking
            $table->boolean('ai_extracted')->default(false);
            $table->decimal('ai_confidence', 3, 2)->nullable();
            
            $table->timestamps();
            
            $table->index(['trip_id', 'type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_segments');
    }
};
