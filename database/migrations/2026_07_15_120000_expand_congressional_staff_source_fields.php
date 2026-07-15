<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congressional_positions', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('normalized_title')->change();
        });

        Schema::table('congressional_staff_observations', function (Blueprint $table) {
            $table->text('title_raw')->change();
        });
    }

    public function down(): void
    {
        Schema::table('congressional_positions', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('normalized_title')->change();
        });

        Schema::table('congressional_staff_observations', function (Blueprint $table) {
            $table->string('title_raw')->change();
        });
    }
};
