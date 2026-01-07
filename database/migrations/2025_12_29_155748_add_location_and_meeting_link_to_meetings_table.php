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
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('location')->nullable()->after('meeting_date');
            $table->string('meeting_link')->nullable()->after('location');
            $table->string('meeting_link_type')->nullable()->after('meeting_link'); // zoom, google_meet, teams, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['location', 'meeting_link', 'meeting_link_type']);
        });
    }
};
