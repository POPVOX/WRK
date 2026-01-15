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
        Schema::create('trip_ground_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('type', [
                'rental_car',
                'taxi',
                'rideshare',
                'public_transit',
                'shuttle',
                'parking',
                'other'
            ]);
            
            $table->string('provider')->nullable(); // Hertz, Uber, etc.
            $table->string('confirmation_number')->nullable();
            
            $table->datetime('pickup_datetime')->nullable();
            $table->string('pickup_location')->nullable();
            $table->datetime('return_datetime')->nullable();
            $table->string('return_location')->nullable();
            
            // Rental car specific
            $table->string('vehicle_type')->nullable();
            $table->string('license_plate')->nullable();
            
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            $table->text('notes')->nullable();
            $table->boolean('ai_extracted')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_ground_transport');
    }
};
