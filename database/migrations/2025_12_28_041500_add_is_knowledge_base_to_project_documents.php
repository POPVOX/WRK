<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('project_documents', 'is_knowledge_base')) {
                    $table->boolean('is_knowledge_base')->default(true)->after('missing_on_disk');
                }
                $table->index(['is_knowledge_base']);
            });

            // Backfill existing docs
            DB::table('project_documents')->update(['is_knowledge_base' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'is_knowledge_base')) {
                    $table->dropColumn('is_knowledge_base');
                }
                $table->dropIndexIfExists('project_documents_is_knowledge_base_index');
            });
        }
    }
};
