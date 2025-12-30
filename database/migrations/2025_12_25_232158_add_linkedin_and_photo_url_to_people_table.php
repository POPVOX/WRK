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
        Schema::table('people', function (Blueprint $table) {
            $table->string('linkedin_url')->nullable()->after('phone');
            $table->string('photo_url')->nullable()->after('linkedin_url');
            $table->text('bio')->nullable()->after('photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'photo_url', 'bio']);
        });
    }
};
