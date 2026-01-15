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
        Schema::create('trip_lodging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('property_name');
            $table->string('chain')->nullable(); // Marriott, Hilton, etc.
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('country', 2);
            
            $table->string('confirmation_number')->nullable();
            $table->date('check_in_date');
            $table->time('check_in_time')->nullable();
            $table->date('check_out_date');
            $table->time('check_out_time')->nullable();
            
            $table->string('room_type')->nullable();
            $table->integer('nights')->nullable();
            $table->decimal('nightly_rate', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // Coordinates for map
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->text('notes')->nullable();
            $table->boolean('ai_extracted')->default(false);
            
            $table->timestamps();
            
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_lodging');
    }
};
