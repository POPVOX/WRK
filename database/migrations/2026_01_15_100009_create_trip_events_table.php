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
        Schema::create('trip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            
            // Can link to existing meeting or be standalone
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('start_datetime');
            $table->datetime('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            
            $table->enum('type', [
                'conference_session',
                'meeting',
                'presentation',
                'workshop',
                'reception',
                'site_visit',
                'other'
            ])->default('other');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_events');
    }
};
