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
        Schema::create('travel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Info
            $table->date('birthday')->nullable();
            $table->text('passport_number_encrypted')->nullable();
            $table->string('passport_country', 2)->nullable(); // ISO country code
            $table->date('passport_expiration')->nullable();
            
            // Travel Programs (encrypted)
            $table->text('tsa_precheck_number_encrypted')->nullable();
            $table->text('global_entry_number_encrypted')->nullable();
            
            // Frequent Flyer Programs (JSON array, encrypted)
            $table->text('frequent_flyer_programs_encrypted')->nullable();
            
            // Hotel Programs (JSON array, encrypted)
            $table->text('hotel_programs_encrypted')->nullable();
            
            // Rental Car Programs (JSON array, encrypted)
            $table->text('rental_car_programs_encrypted')->nullable();
            
            // Preferences
            $table->enum('seat_preference', ['window', 'aisle', 'middle', 'no_preference'])->default('no_preference');
            $table->text('dietary_restrictions')->nullable();
            $table->text('travel_notes')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_email')->nullable();
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_profiles');
    }
};
