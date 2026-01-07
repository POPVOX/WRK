<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_searched_at')->nullable();
            $table->integer('clips_found')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_search_terms');
    }
};
