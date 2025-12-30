<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add initiative fields to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_initiative')->default(false)->after('status');
            $table->text('project_path')->nullable()->after('is_initiative');
            $table->json('success_metrics')->nullable()->after('project_path');
        });

        // Workstreams for organizing sub-projects
        Schema::create('project_workstreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366f1');
            $table->string('icon', 10)->default('📁');
            $table->enum('status', ['planning', 'active', 'completed', 'paused'])->default('planning');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Publications (chapters, reports, briefs)
        Schema::create('project_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('workstream_id')->nullable()->constrained('project_workstreams')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['chapter', 'report', 'brief', 'appendix', 'case_study', 'other'])->default('chapter');
            $table->enum('status', ['idea', 'outlined', 'drafting', 'editing', 'review', 'ready', 'published'])->default('idea');
            $table->date('target_date')->nullable();
            $table->date('published_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->text('content_path')->nullable();
            $table->timestamps();
        });

        // Events (staff events, demos, launches)
        Schema::create('project_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('workstream_id')->nullable()->constrained('project_workstreams')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['staff_event', 'demo', 'launch', 'briefing', 'workshop', 'other'])->default('staff_event');
            $table->enum('status', ['planning', 'confirmed', 'completed', 'cancelled'])->default('planning');
            $table->dateTime('event_date')->nullable();
            $table->string('location')->nullable();
            $table->integer('target_attendees')->nullable();
            $table->integer('actual_attendees')->nullable();
            $table->json('deliverables')->nullable();
            $table->timestamps();
        });

        // Add new fields to existing project_milestones table
        if (Schema::hasTable('project_milestones')) {
            Schema::table('project_milestones', function (Blueprint $table) {
                if (!Schema::hasColumn('project_milestones', 'workstream_id')) {
                    $table->foreignId('workstream_id')->nullable()->after('project_id')->constrained('project_workstreams')->onDelete('set null');
                }
                if (!Schema::hasColumn('project_milestones', 'publication_id')) {
                    $table->foreignId('publication_id')->nullable()->after('workstream_id')->constrained('project_publications')->onDelete('set null');
                }
                if (!Schema::hasColumn('project_milestones', 'event_id')) {
                    $table->foreignId('event_id')->nullable()->after('publication_id')->constrained('project_events')->onDelete('set null');
                }
                if (!Schema::hasColumn('project_milestones', 'due_date')) {
                    $table->date('due_date')->nullable()->after('description');
                }
            });
        }

        // Add new fields to existing project_documents table
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('project_documents', 'workstream_id')) {
                    $table->foreignId('workstream_id')->nullable()->after('project_id')->constrained('project_workstreams')->onDelete('set null');
                }
                if (!Schema::hasColumn('project_documents', 'ai_indexed')) {
                    $table->boolean('ai_indexed')->default(false)->after('file_size');
                }
                if (!Schema::hasColumn('project_documents', 'ai_summary')) {
                    $table->text('ai_summary')->nullable()->after('ai_indexed');
                }
                if (!Schema::hasColumn('project_documents', 'file_type')) {
                    $table->string('file_type', 20)->nullable()->after('file_path');
                }
            });
        }

        // AI chat history per project
        Schema::create('project_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_chat_messages');

        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'workstream_id')) {
                    $table->dropForeign(['workstream_id']);
                    $table->dropColumn('workstream_id');
                }
                if (Schema::hasColumn('project_documents', 'ai_indexed')) {
                    $table->dropColumn('ai_indexed');
                }
                if (Schema::hasColumn('project_documents', 'ai_summary')) {
                    $table->dropColumn('ai_summary');
                }
            });
        }

        if (Schema::hasTable('project_milestones')) {
            Schema::table('project_milestones', function (Blueprint $table) {
                if (Schema::hasColumn('project_milestones', 'workstream_id')) {
                    $table->dropForeign(['workstream_id']);
                    $table->dropColumn('workstream_id');
                }
                if (Schema::hasColumn('project_milestones', 'publication_id')) {
                    $table->dropForeign(['publication_id']);
                    $table->dropColumn('publication_id');
                }
                if (Schema::hasColumn('project_milestones', 'event_id')) {
                    $table->dropForeign(['event_id']);
                    $table->dropColumn('event_id');
                }
            });
        }

        Schema::dropIfExists('project_events');
        Schema::dropIfExists('project_publications');
        Schema::dropIfExists('project_workstreams');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['is_initiative', 'project_path', 'success_metrics']);
        });
    }
};
