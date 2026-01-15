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
        Schema::create('trip_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            
            $table->integer('order')->default(1);
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('country', 2); // ISO code
            $table->string('region')->nullable();
            
            $table->date('arrival_date');
            $table->date('departure_date');
            
            // Policy info (auto-calculated)
            $table->string('state_dept_level')->nullable(); // 1, 2, 3, 4
            $table->boolean('is_prohibited_destination')->default(false);
            $table->text('travel_advisory_notes')->nullable();
            
            // Coordinates for map
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->timestamps();
            
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_destinations');
    }
};
