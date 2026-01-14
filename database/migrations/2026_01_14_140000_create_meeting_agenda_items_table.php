<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable(); // Estimated time for this item
            $table->foreignId('presenter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->text('notes')->nullable(); // Notes taken during discussion
            $table->text('decisions')->nullable(); // Decisions made on this item
            $table->timestamps();
            
            $table->index(['meeting_id', 'order']);
        });

        // Also add an agenda_notes field to the meetings table for free-form agenda
        Schema::table('meetings', function (Blueprint $table) {
            $table->text('agenda_notes')->nullable()->after('prep_notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_agenda_items');
        
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('agenda_notes');
        });
    }
};

