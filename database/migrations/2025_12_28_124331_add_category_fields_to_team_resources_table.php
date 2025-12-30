<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('team_resources', function (Blueprint $table) {
            // category already exists in original table
            $table->string('audience')->default('all')->after('category'); // all, staff, management, admin
            $table->date('last_reviewed')->nullable()->after('audience');
            $table->integer('review_frequency_days')->nullable()->after('last_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_resources', function (Blueprint $table) {
            $table->dropColumn(['audience', 'last_reviewed', 'review_frequency_days']);
        });
    }
};
