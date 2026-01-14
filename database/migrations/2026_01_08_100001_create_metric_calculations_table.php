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
        Schema::create('metric_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained()->onDelete('cascade');
            $table->foreignId('schema_id')->nullable()->constrained('grant_reporting_schemas')->onDelete('set null');
            $table->date('reporting_period_start');
            $table->date('reporting_period_end');
            $table->string('metric_id'); // e.g., "outcome_8.bipartisan_briefs"
            $table->json('calculated_value')->nullable(); // {value, items[], filters_applied, target, status}
            $table->text('manual_value')->nullable(); // For manual narrative entries
            $table->enum('calculation_method', ['auto', 'manual', 'hybrid'])->default('auto');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['grant_id', 'reporting_period_start', 'reporting_period_end'], 'idx_grant_period');
            $table->unique(
                ['grant_id', 'metric_id', 'reporting_period_start', 'reporting_period_end'],
                'unique_metric_period'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_calculations');
    }
};

