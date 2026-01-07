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
        Schema::table('grants', function (Blueprint $table) {
            $table->string('scope')->nullable()->after('visibility'); // us, global, specific project
            $table->foreignId('primary_project_id')->nullable()->after('scope')
                ->constrained('projects')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grants', function (Blueprint $table) {
            $table->dropForeign(['primary_project_id']);
            $table->dropColumn(['scope', 'primary_project_id']);
        });
    }
};
