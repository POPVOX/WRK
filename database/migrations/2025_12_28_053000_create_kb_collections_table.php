<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kb_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->text('query')->nullable();
            $table->json('filters')->nullable(); // projectId, type, ext, tag
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_collections');
    }
};
