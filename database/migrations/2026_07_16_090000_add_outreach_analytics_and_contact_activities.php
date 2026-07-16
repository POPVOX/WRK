<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_campaign_recipients', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('external_message_id');
            $table->timestamp('opened_at')->nullable()->index()->after('sent_at');
            $table->timestamp('clicked_at')->nullable()->index()->after('opened_at');
            $table->timestamp('replied_at')->nullable()->index()->after('clicked_at');
            $table->timestamp('bounced_at')->nullable()->index()->after('replied_at');
            $table->timestamp('unsubscribed_at')->nullable()->index()->after('bounced_at');
            $table->unsignedInteger('open_count')->default(0)->after('unsubscribed_at');
            $table->unsignedInteger('click_count')->default(0)->after('open_count');
        });

        Schema::create('outreach_recipient_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_recipient_id')->constrained('outreach_campaign_recipients')->cascadeOnDelete();
            $table->string('event_type', 32)->index();
            $table->char('event_key', 64)->unique();
            $table->text('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['campaign_recipient_id', 'occurred_at']);
        });

        Schema::create('contact_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('congressional_staff_profile_id')->nullable()->constrained('congressional_staff_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained('outreach_campaign_recipients')->nullOnDelete();
            $table->foreignId('gmail_message_id')->nullable()->constrained('gmail_messages')->nullOnDelete();
            $table->string('activity_type', 32)->index();
            $table->string('direction', 16)->nullable()->index();
            $table->string('subject')->nullable();
            $table->text('summary')->nullable();
            $table->string('source_type', 32)->default('manual')->index();
            $table->char('source_key', 64)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['person_id', 'occurred_at']);
            $table->index(['congressional_staff_profile_id', 'occurred_at'], 'contact_activities_staff_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_activities');
        Schema::dropIfExists('outreach_recipient_events');

        Schema::table('outreach_campaign_recipients', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_token', 'opened_at', 'clicked_at', 'replied_at', 'bounced_at',
                'unsubscribed_at', 'open_count', 'click_count',
            ]);
        });
    }
};
