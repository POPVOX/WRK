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
        // Add accomplishment preferences to users
        Schema::table('users', function (Blueprint $table) {
            $table->json('accomplishment_preferences')->nullable()->after('is_admin');
        });

        // Add organizer_id to meetings for tracking who organized
        Schema::table('meetings', function (Blueprint $table) {
            if (! Schema::hasColumn('meetings', 'organizer_id')) {
                $table->foreignId('organizer_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            }
        });

        // Add owner and contributors to projects
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('projects', 'contributors')) {
                $table->json('contributors')->nullable()->after('owner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('accomplishment_preferences');
        });

        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'organizer_id')) {
                $table->dropForeign(['organizer_id']);
                $table->dropColumn('organizer_id');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'owner_id')) {
                $table->dropForeign(['owner_id']);
                $table->dropColumn('owner_id');
            }
            if (Schema::hasColumn('projects', 'contributors')) {
                $table->dropColumn('contributors');
            }
        });
    }
};

