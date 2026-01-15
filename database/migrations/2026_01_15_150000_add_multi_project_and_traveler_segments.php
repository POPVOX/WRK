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
        // Create pivot table for trips <-> projects (many-to-many)
        Schema::create('project_trip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['trip_id', 'project_id']);
        });

        // Add user_id to trip_segments for per-traveler itineraries
        Schema::table('trip_segments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('trip_id')->constrained()->nullOnDelete();
        });

        // Add user_id to trip_lodging for per-traveler lodging
        Schema::table('trip_lodging', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('trip_id')->constrained()->nullOnDelete();
        });

        // Add user_id to trip_ground_transport for per-traveler ground transport
        Schema::table('trip_ground_transport', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('trip_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_trip');

        Schema::table('trip_segments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('trip_lodging', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('trip_ground_transport', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
