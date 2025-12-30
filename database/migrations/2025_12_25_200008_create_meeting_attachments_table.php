<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meeting_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 50)->nullable(); // image, pdf, document
            $table->string('original_filename')->nullable();
            $table->string('description')->nullable(); // e.g., "Leave-behind", "Participant photo", "One-pager"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attachments');
    }
};
