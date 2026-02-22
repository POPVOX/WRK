<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outreach_newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 191)->unique();
            $table->string('channel', 24)->default('hybrid');
            $table->string('status', 32)->default('draft');
            $table->string('cadence', 32)->nullable();
            $table->json('audience_filters')->nullable();
            $table->text('planning_notes')->nullable();
            $table->json('publishing_checklist')->nullable();
            $table->date('next_issue_date')->nullable();
            $table->timestamp('last_issue_sent_at')->nullable();
            $table->string('substack_publication_url')->nullable();
            $table->string('substack_section')->nullable();
            $table->string('default_subject_prefix')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_issue_date']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('outreach_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->nullable()->constrained('outreach_newsletters')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('name', 180);
            $table->string('campaign_type', 32)->default('bulk');
            $table->string('channel', 24)->default('gmail');
            $table->string('status', 32)->default('draft');
            $table->string('subject')->nullable();
            $table->string('preheader')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_markdown')->nullable();
            $table->string('send_mode', 24)->default('immediate');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['user_id', 'status']);
            $table->index(['newsletter_id', 'status']);
        });

        Schema::create('outreach_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('outreach_campaigns')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('external_message_id', 191)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'email']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('outreach_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('newsletter_id')->nullable()->constrained('outreach_newsletters')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('name', 180);
            $table->string('status', 24)->default('active');
            $table->string('trigger_type', 24)->default('schedule');
            $table->string('rrule', 255)->nullable();
            $table->string('timezone', 80)->nullable();
            $table->string('action_type', 48)->default('draft_campaign');
            $table->text('prompt')->nullable();
            $table->json('config')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_run_at']);
            $table->index(['newsletter_id', 'status']);
        });

        Schema::create('outreach_substack_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('publication_name')->nullable();
            $table->string('publication_url')->nullable();
            $table->string('rss_feed_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('email_from')->nullable();
            $table->string('status', 24)->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'last_synced_at']);
        });

        Schema::create('outreach_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('newsletter_id')->nullable()->constrained('outreach_newsletters')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('outreach_campaigns')->nullOnDelete();
            $table->foreignId('automation_id')->nullable()->constrained('outreach_automations')->nullOnDelete();
            $table->string('action', 80);
            $table->string('summary')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_activity_logs');
        Schema::dropIfExists('outreach_substack_connections');
        Schema::dropIfExists('outreach_automations');
        Schema::dropIfExists('outreach_campaign_recipients');
        Schema::dropIfExists('outreach_campaigns');
        Schema::dropIfExists('outreach_newsletters');
    }
};

