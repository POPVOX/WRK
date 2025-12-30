<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('project_documents', 'suggested_tags')) {
                    $table->json('suggested_tags')->nullable()->after('tags');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (Schema::hasColumn('project_documents', 'suggested_tags')) {
                    $table->dropColumn('suggested_tags');
                }
            });
        }
    }
};
