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
        Schema::create('trip_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Null = applies to all
            
            $table->string('item');
            $table->enum('category', [
                'documents',
                'electronics',
                'clothing',
                'presentation_materials',
                'gifts_swag',
                'health_safety',
                'other'
            ])->default('other');
            
            $table->boolean('is_completed')->default(false);
            $table->boolean('ai_suggested')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_checklists');
    }
};
