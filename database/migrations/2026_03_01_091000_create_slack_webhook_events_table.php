<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id', 191)->unique();
            $table->string('event_type', 120)->nullable();
            $table->string('slack_user_id', 64)->nullable();
            $table->string('slack_channel_id', 64)->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('status', 32)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
            $table->index(['slack_channel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_webhook_events');
    }
};
