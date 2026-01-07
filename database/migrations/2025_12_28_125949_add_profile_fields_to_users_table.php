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
        Schema::table('users', function (Blueprint $table) {
            $table->string('location')->nullable()->after('photo_url');
            $table->string('timezone')->default('America/New_York')->after('location');
            $table->text('bio_short')->nullable()->after('bio'); // One-liner
            $table->text('bio_medium')->nullable()->after('bio_short'); // Paragraph
            $table->json('publications')->nullable()->after('bio_medium'); // List of publications
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['location', 'timezone', 'bio_short', 'bio_medium', 'publications']);
        });
    }
};
