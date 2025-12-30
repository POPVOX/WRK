<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->json('filters')->nullable(); // search, org, status, owner, tag, viewMode
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_views');
    }
};
