<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            // Add file_type column for tracking document extensions
            if (! Schema::hasColumn('project_documents', 'file_type')) {
                $table->string('file_type', 20)->nullable()->after('file_path');
            }

            // Add AI-related columns
            if (! Schema::hasColumn('project_documents', 'ai_indexed')) {
                $table->boolean('ai_indexed')->default(false)->after('uploaded_by');
            }

            if (! Schema::hasColumn('project_documents', 'ai_summary')) {
                $table->text('ai_summary')->nullable()->after('ai_indexed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $columns = ['file_type', 'ai_indexed', 'ai_summary'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('project_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
