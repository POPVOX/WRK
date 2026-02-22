<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('gmail_import_date')->nullable()->after('calendar_import_date');
            $table->string('gmail_history_id', 64)->nullable()->after('gmail_import_date');
        });

        Schema::create('gmail_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->string('gmail_message_id', 128);
            $table->string('gmail_thread_id', 128)->nullable()->index();
            $table->string('history_id', 64)->nullable();
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable();
            $table->string('from_email')->nullable()->index();
            $table->string('from_name')->nullable();
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->boolean('is_inbound')->default(true)->index();
            $table->json('labels')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'gmail_message_id']);
            $table->index(['user_id', 'sent_at']);
            $table->index(['user_id', 'gmail_thread_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmail_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gmail_import_date', 'gmail_history_id']);
        });
    }
};
