<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reporting_requirements')) {
            return;
        }

        Schema::create('reporting_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('grant_documents')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('narrative');
            $table->date('due_date');
            $table->string('status')->default('upcoming');
            $table->text('format_requirements')->nullable();
            $table->string('template_url')->nullable();
            $table->foreignId('submitted_document_id')->nullable()->constrained('project_documents')->nullOnDelete();
            $table->date('submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('source_quote')->nullable();
            $table->string('metric_id')->nullable();
            $table->boolean('auto_calculated')->default(false);
            $table->timestamps();

            $table->index(['grant_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        // Intentionally preserve the table: this migration may have found an
        // existing production table rather than creating a replacement.
    }
};
