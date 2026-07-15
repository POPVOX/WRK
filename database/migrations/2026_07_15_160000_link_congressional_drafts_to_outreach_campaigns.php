<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_campaigns', function (Blueprint $table) {
            $table->foreignId('congressional_outreach_draft_id')
                ->nullable()
                ->after('project_id');
            $table->foreign('congressional_outreach_draft_id', 'outreach_campaigns_draft_fk')
                ->references('id')
                ->on('congressional_outreach_drafts')
                ->nullOnDelete();
            $table->index(['congressional_outreach_draft_id', 'status'], 'outreach_campaigns_draft_status_index');
        });

        Schema::table('outreach_campaign_recipients', function (Blueprint $table) {
            $table->foreignId('congressional_outreach_draft_recipient_id')
                ->nullable()
                ->after('person_id');
            $table->foreign('congressional_outreach_draft_recipient_id', 'outreach_recipients_draft_recipient_fk')
                ->references('id')
                ->on('congressional_outreach_draft_recipients')
                ->nullOnDelete();
            $table->unique('congressional_outreach_draft_recipient_id', 'outreach_recipients_draft_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_campaign_recipients', function (Blueprint $table) {
            $table->dropUnique('outreach_recipients_draft_recipient_unique');
            $table->dropForeign('outreach_recipients_draft_recipient_fk');
            $table->dropColumn('congressional_outreach_draft_recipient_id');
        });

        Schema::table('outreach_campaigns', function (Blueprint $table) {
            $table->dropIndex('outreach_campaigns_draft_status_index');
            $table->dropForeign('outreach_campaigns_draft_fk');
            $table->dropColumn('congressional_outreach_draft_id');
        });
    }
};
