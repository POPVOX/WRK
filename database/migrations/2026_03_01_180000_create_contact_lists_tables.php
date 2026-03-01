<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        Schema::create('contact_list_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_list_id')->constrained('contact_lists')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contact_list_id', 'person_id']);
            $table->index(['person_id', 'contact_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_list_person');
        Schema::dropIfExists('contact_lists');
    }
};
