<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['new', 'responding', 'completed', 'declined', 'no_response'])->default('new');
            $table->enum('urgency', ['standard', 'urgent', 'breaking'])->default('standard');
            $table->timestamp('received_at');
            $table->timestamp('deadline')->nullable();
            $table->foreignId('journalist_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('journalist_name')->nullable();
            $table->string('journalist_email')->nullable();
            $table->foreignId('outlet_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('outlet_name')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response_notes')->nullable();
            $table->foreignId('resulting_clip_id')->nullable()->constrained('press_clips')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index('urgency');
            $table->index('deadline');
        });

        // Pivot: inquiries <-> issues
        Schema::create('inquiry_issue', function (Blueprint $table) {
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->primary(['inquiry_id', 'issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_issue');
        Schema::dropIfExists('inquiries');
    }
};
