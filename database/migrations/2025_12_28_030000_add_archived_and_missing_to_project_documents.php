<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('project_documents', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('ai_summary');
                }
                if (! Schema::hasColumn('project_documents', 'missing_on_disk')) {
                    $table->boolean('missing_on_disk')->default(false)->after('is_archived');
                }

                $table->index(['is_archived']);
                $table->index(['missing_on_disk']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'is_archived')) {
                    $table->dropColumn('is_archived');
                }
                if (Schema::hasColumn('project_documents', 'missing_on_disk')) {
                    $table->dropColumn('missing_on_disk');
                }

                $table->dropIndexIfExists('project_documents_is_archived_index');
                $table->dropIndexIfExists('project_documents_missing_on_disk_index');
            });
        }
    }
};
