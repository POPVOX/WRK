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
        Schema::table('trip_segments', function (Blueprint $table) {
            $table->foreignId('trip_guest_id')->nullable()->after('user_id')
                ->constrained('trip_guests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_segments', function (Blueprint $table) {
            $table->dropForeign(['trip_guest_id']);
            $table->dropColumn('trip_guest_id');
        });
    }
};
