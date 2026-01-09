<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add grant associations and metric tags to meetings
        Schema::table('meetings', function (Blueprint $table) {
            $table->json('grant_associations')->nullable()->after('status');
            $table->json('metric_tags')->nullable()->after('grant_associations');
            $table->integer('external_organizations_count')->default(0)->after('metric_tags');
        });

        // Add grant associations and metric tags to project_documents
        Schema::table('project_documents', function (Blueprint $table) {
            $table->json('grant_associations')->nullable()->after('is_knowledge_base');
            $table->json('metric_tags')->nullable()->after('grant_associations');
            $table->string('document_type')->nullable()->after('metric_tags'); // policy_brief, testimony, etc.
        });

        // Add grant associations and metric tags to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->json('grant_associations')->nullable()->after('status');
            $table->json('metric_tags')->nullable()->after('grant_associations');
        });

        // Add contact_type and political_affiliation to people
        Schema::table('people', function (Blueprint $table) {
            $table->string('contact_type')->nullable()->after('role'); // government_official, funder, grantee, partner
            $table->string('political_affiliation')->nullable()->after('contact_type'); // bipartisan, progressive, conservative
        });

        // Add metric linkage to reporting_requirements
        Schema::table('reporting_requirements', function (Blueprint $table) {
            $table->string('metric_id')->nullable()->after('notes'); // Links to schema metric
            $table->boolean('auto_calculated')->default(false)->after('metric_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['grant_associations', 'metric_tags', 'external_organizations_count']);
        });

        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropColumn(['grant_associations', 'metric_tags', 'document_type']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['grant_associations', 'metric_tags']);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['contact_type', 'political_affiliation']);
        });

        Schema::table('reporting_requirements', function (Blueprint $table) {
            $table->dropColumn(['metric_id', 'auto_calculated']);
        });
    }
};
