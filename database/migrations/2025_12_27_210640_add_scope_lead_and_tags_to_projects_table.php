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
            $table->string('scope')->nullable()->after('name'); // US, Global, Comms, etc.
            $table->string('lead')->nullable()->after('scope'); // Lead person name
            $table->string('url')->nullable()->after('goals'); // External link/reference
            $table->json('tags')->nullable()->after('url'); // Themes/tags as JSON array
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['scope', 'lead', 'url', 'tags']);
        });
    }
};
