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
        Schema::create('reporting_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('narrative'); // narrative, financial, both
            $table->date('due_date');
            $table->string('status')->default('upcoming'); // upcoming, in_progress, submitted
            $table->text('format_requirements')->nullable();
            $table->string('template_url')->nullable();
            $table->foreignId('submitted_document_id')->nullable()->constrained('project_documents')->nullOnDelete();
            $table->date('submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporting_requirements');
    }
};
