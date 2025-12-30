<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('project_documents', 'tags')) {
                    $table->json('tags')->nullable()->after('is_knowledge_base');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'tags')) {
                    $table->dropColumn('tags');
                }
            });
        }
    }
};
