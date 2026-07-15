<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congressional_staff_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('congressional_staff_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congressional_staff_list_id')->constrained('congressional_staff_lists')->cascadeOnDelete();
            $table->foreignId('congressional_staff_profile_id')->constrained('congressional_staff_profiles')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['congressional_staff_list_id', 'congressional_staff_profile_id'],
                'congressional_staff_list_profile_unique'
            );
            $table->index('congressional_staff_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congressional_staff_list_members');
        Schema::dropIfExists('congressional_staff_lists');
    }
};
