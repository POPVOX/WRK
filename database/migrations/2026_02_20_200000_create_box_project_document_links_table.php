<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_project_document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_item_id')->constrained('box_items')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_document_id')->nullable()->constrained('project_documents')->nullOnDelete();
            $table->string('visibility', 32)->default('all');
            $table->string('sync_status', 32)->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['box_item_id', 'project_id']);
            $table->index(['project_id', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_project_document_links');
    }
};
