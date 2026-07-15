<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congressional_staff_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('congressional_staff_profiles')->cascadeOnDelete();
            $table->string('email');
            $table->string('email_normalized')->index();
            $table->string('source_type', 24)->default('manual')->index();
            $table->string('verification_status', 32)->default('unverified')->index();
            $table->boolean('is_primary')->default(false);
            $table->text('source_url')->nullable();
            $table->text('source_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('first_observed_at')->nullable();
            $table->timestamp('last_observed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->timestamp('hard_bounced_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['profile_id', 'email_normalized']);
            $table->index(['profile_id', 'verification_status']);
        });

        Schema::create('congressional_staff_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_email_id')->constrained('congressional_staff_emails')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gmail_message_id')->nullable()->constrained('gmail_messages')->nullOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained('outreach_campaign_recipients')->nullOnDelete();
            $table->char('event_key', 64)->unique();
            $table->string('event_type', 40)->index();
            $table->string('evidence_strength', 16)->default('low');
            $table->text('evidence_excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['staff_email_id', 'occurred_at']);
        });

        Schema::create('outreach_email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email_normalized')->unique();
            $table->string('reason', 32)->index();
            $table->string('source_type', 32)->default('manual');
            $table->foreignId('gmail_message_id')->nullable()->constrained('gmail_messages')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('suppressed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_email_suppressions');
        Schema::dropIfExists('congressional_staff_email_events');
        Schema::dropIfExists('congressional_staff_emails');
    }
};
