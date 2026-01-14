<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add meeting time (#7)
        Schema::table('meetings', function (Blueprint $table) {
            $table->time('meeting_time')->nullable()->after('meeting_date');
            $table->time('meeting_end_time')->nullable()->after('meeting_time');
        });

        // Add project tasks table (#14)
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        // Add milestone edit tracking (#15)
        Schema::table('project_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('project_milestones', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['meeting_time', 'meeting_end_time']);
        });

        Schema::dropIfExists('project_tasks');

        Schema::table('project_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('project_milestones', 'completed_by')) {
                $table->dropForeign(['completed_by']);
                $table->dropColumn('completed_by');
            }
        });
    }
};

