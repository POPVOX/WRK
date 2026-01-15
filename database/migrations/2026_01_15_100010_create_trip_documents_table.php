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
        Schema::create('trip_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('storage_path');
            
            $table->enum('type', [
                'itinerary',
                'confirmation',
                'receipt',
                'invoice',
                'boarding_pass',
                'visa',
                'insurance',
                'agenda',
                'presentation',
                'other'
            ])->default('other');
            
            $table->text('description')->nullable();
            
            // For AI extraction source tracking
            $table->boolean('ai_processed')->default(false);
            $table->timestamp('ai_processed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_documents');
    }
};
