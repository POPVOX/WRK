<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->text('body_text')->nullable()->after('snippet');
        });

        Schema::create('congressional_staff_change_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gmail_message_id')->constrained('gmail_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained('congressional_staff_profiles')->nullOnDelete();
            $table->char('signal_key', 64)->unique();
            $table->string('signal_type', 40)->index();
            $table->string('status', 24)->default('pending')->index();
            $table->string('source_email')->nullable()->index();
            $table->json('target_emails')->nullable();
            $table->json('replacement_contacts')->nullable();
            $table->text('summary');
            $table->text('evidence_excerpt')->nullable();
            $table->timestamp('detected_at')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'signal_type', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congressional_staff_change_signals');

        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->dropColumn('body_text');
        });
    }
};
