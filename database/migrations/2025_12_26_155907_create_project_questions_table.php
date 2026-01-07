<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->text('question');
            $table->text('context')->nullable();

            $table->string('status')->default('open'); // open, answered

            $table->text('answer')->nullable();
            $table->date('answered_date')->nullable();
            $table->foreignId('answered_in_meeting_id')->nullable()->constrained('meetings')->nullOnDelete();

            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_questions');
    }
};
