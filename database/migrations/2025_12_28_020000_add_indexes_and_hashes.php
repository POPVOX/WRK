<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // project_documents: add hash + last_seen_at + indexes
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('project_documents', 'content_hash')) {
                    $table->string('content_hash')->nullable()->after('file_size');
                }
                if (! Schema::hasColumn('project_documents', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->after('content_hash');
                }

                $table->index(['project_id']);
                $table->index(['file_path']);
                $table->index(['ai_indexed']);
                $table->index(['content_hash']);
            });
        }

        // project_events indexes
        if (Schema::hasTable('project_events')) {
            Schema::table('project_events', function (Blueprint $table) {
                $table->index(['project_id']);
                $table->index(['status']);
                $table->index(['event_date']);
            });
        }

        // project_publications indexes
        if (Schema::hasTable('project_publications')) {
            Schema::table('project_publications', function (Blueprint $table) {
                $table->index(['project_id']);
                $table->index(['status']);
                $table->index(['target_date']);
                $table->index(['published_date']);
            });
        }

        // project_milestones indexes
        if (Schema::hasTable('project_milestones')) {
            Schema::table('project_milestones', function (Blueprint $table) {
                $table->index(['project_id']);
                $table->index(['status']);
                $table->index(['due_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'content_hash')) {
                    $table->dropColumn('content_hash');
                }
                if (Schema::hasColumn('project_documents', 'last_seen_at')) {
                    $table->dropColumn('last_seen_at');
                }

                $table->dropIndexIfExists('project_documents_project_id_index');
                $table->dropIndexIfExists('project_documents_file_path_index');
                $table->dropIndexIfExists('project_documents_ai_indexed_index');
                $table->dropIndexIfExists('project_documents_content_hash_index');
            });
        }

        foreach ([
            'project_events' => ['project_id', 'status', 'event_date'],
            'project_publications' => ['project_id', 'status', 'target_date', 'published_date'],
            'project_milestones' => ['project_id', 'status', 'due_date'],
        ] as $tableName => $cols) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $cols) {
                    foreach ($cols as $col) {
                        $table->dropIndexIfExists("{$tableName}_{$col}_index");
                    }
                });
            }
        }
    }
};
