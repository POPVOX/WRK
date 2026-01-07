<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('parent_project_id')->nullable()->after('id')->constrained('projects')->nullOnDelete();
            $table->string('project_type')->default('initiative')->after('status'); // initiative, publication, event, chapter, component, tool
            $table->integer('sort_order')->default(0)->after('project_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_project_id');
            $table->dropColumn(['project_type', 'sort_order']);
        });
    }
};
