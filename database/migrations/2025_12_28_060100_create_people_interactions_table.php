<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('people_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type')->index(); // call, email, meeting, note
            $table->timestamp('occurred_at')->nullable()->index();
            $table->text('summary')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->string('next_action_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_interactions');
    }
};
