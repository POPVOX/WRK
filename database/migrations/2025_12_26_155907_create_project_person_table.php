<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable(); // lead contact, champion, decision maker
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_person');
    }
};
