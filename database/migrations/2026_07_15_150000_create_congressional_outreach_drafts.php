<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congressional_outreach_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congressional_staff_list_id')->constrained('congressional_staff_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['congressional_staff_list_id', 'created_at']);
        });

        Schema::create('congressional_outreach_draft_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained('congressional_outreach_drafts')->cascadeOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained('congressional_staff_profiles')->nullOnDelete();
            $table->foreignId('staff_email_id')->nullable()->constrained('congressional_staff_emails')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('email_normalized')->nullable()->index();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('office')->nullable();
            $table->string('eligibility_tier', 16)->default('blocked')->index();
            $table->string('source_type', 24)->nullable();
            $table->string('verification_status', 32)->nullable();
            $table->string('review_status', 24)->default('pending')->index();
            $table->string('exclusion_reason', 48)->nullable();
            $table->text('selection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['draft_id', 'profile_id']);
            $table->index(['draft_id', 'review_status']);
            $table->index(['draft_id', 'eligibility_tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congressional_outreach_draft_recipients');
        Schema::dropIfExists('congressional_outreach_drafts');
    }
};
