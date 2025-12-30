<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // The decision
            $table->string('title');
            $table->text('description');

            // Context and rationale (THE KEY FIELDS - capture the "why")
            $table->text('rationale')->nullable();
            $table->text('context')->nullable();

            // Linkages
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();

            // Metadata
            $table->date('decision_date')->nullable();
            $table->string('decided_by')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_decisions');
    }
};
