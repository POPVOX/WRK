<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category'); // 'policy', 'resource', 'howto', 'template'
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('icon')->nullable(); // emoji or icon name
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_resources');
    }
};
