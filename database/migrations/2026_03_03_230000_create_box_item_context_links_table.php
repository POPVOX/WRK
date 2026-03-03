<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_item_context_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_item_id')->constrained('box_items')->cascadeOnDelete();
            $table->string('link_type', 32); // project, meeting, funder
            $table->unsignedBigInteger('link_id');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['box_item_id', 'link_type', 'link_id'], 'box_item_context_links_unique');
            $table->index(['link_type', 'link_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_item_context_links');
    }
};

