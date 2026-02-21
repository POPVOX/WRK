<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_items', function (Blueprint $table) {
            $table->id();
            $table->string('box_item_id')->unique();
            $table->string('box_item_type', 32);
            $table->string('name');
            $table->string('parent_box_folder_id')->nullable();
            $table->text('path_display')->nullable();
            $table->string('etag')->nullable();
            $table->string('sha1')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('owned_by_login')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->timestamp('trashed_at')->nullable();
            $table->json('permissions')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('parent_box_folder_id');
            $table->index(['box_item_type', 'modified_at']);
        });

        Schema::create('box_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id')->unique();
            $table->string('trigger')->nullable();
            $table->string('source_type', 32)->nullable();
            $table->string('source_id')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('status', 32)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('source_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_webhook_events');
        Schema::dropIfExists('box_items');
    }
};
