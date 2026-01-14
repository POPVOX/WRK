<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legislative Reports table
        Schema::create('legislative_reports', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year', 10);                    // e.g., 'FY2026'
            $table->enum('report_type', ['house', 'senate']);
            $table->string('report_number', 50);                  // e.g., '119-178'
            $table->text('title');
            $table->date('enactment_date')->nullable();           // When appropriations enacted
            $table->string('document_path', 255)->nullable();     // PDF storage path
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('fiscal_year');
            $table->index('report_number');
        });

        // Reporting Requirements table
        Schema::create('reporting_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislative_report_id')->constrained()->onDelete('cascade');
            $table->enum('category', ['new', 'prior_year', 'ongoing']);
            $table->text('report_title');
            $table->string('responsible_agency', 255);            // AOC, USCP, CAO, GAO, CRS, etc.
            $table->enum('timeline_type', ['days_from_enactment', 'days_from_report', 'quarterly', 'annual', 'specific_date']);
            $table->integer('timeline_value')->nullable();        // For days-based timelines
            $table->date('due_date')->nullable();                 // Calculated or specific
            $table->text('description');
            $table->text('reporting_recipients');                 // Which committees
            $table->string('source_page_reference', 50)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'overdue'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('responsible_agency');
            $table->index('category');
        });

        // Requirement Reminders table
        Schema::create('requirement_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporting_requirement_id')->constrained()->onDelete('cascade');
            $table->date('reminder_date');
            $table->integer('days_before_due');                   // 7, 14, 30 days before
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('reminder_date');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_reminders');
        Schema::dropIfExists('reporting_requirements');
        Schema::dropIfExists('legislative_reports');
    }
};

