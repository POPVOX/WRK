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
        // Add home airport to travel profiles
        Schema::table('travel_profiles', function (Blueprint $table) {
            $table->string('home_airport_code', 5)->nullable()->after('birthday');
            $table->string('home_airport_name')->nullable()->after('home_airport_code');
        });

        // Create trip_guests table for non-staff travelers
        Schema::create('trip_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->string('role')->nullable(); // e.g., "Speaker", "Partner", "Family"
            $table->text('notes')->nullable();
            
            // Optional travel details
            $table->string('home_airport_code', 5)->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_guests');

        Schema::table('travel_profiles', function (Blueprint $table) {
            $table->dropColumn(['home_airport_code', 'home_airport_name']);
        });
    }
};
