<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_thread_context_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('thread_key', 191)->index();
            $table->string('gmail_thread_id', 128)->nullable()->index();
            $table->string('link_type', 50)->index();
            $table->unsignedBigInteger('link_id')->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'thread_key', 'link_type', 'link_id'], 'inbox_thread_context_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_thread_context_links');
    }
};
