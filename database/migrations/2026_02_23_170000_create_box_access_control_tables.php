<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_access_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_key', 120)->unique();
            $table->string('tier', 24)->default('tier1');
            $table->string('box_folder_id', 64);
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('default_access', 24)->default('read_write');
            $table->boolean('managed_by_wrk')->default(true);
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('box_folder_id');
            $table->index(['tier', 'active']);
        });

        Schema::create('box_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('box_access_policies')->cascadeOnDelete();
            $table->string('subject_type', 24)->default('user');
            $table->foreignId('subject_id')->constrained('users')->cascadeOnDelete();
            $table->string('wrk_permission', 24)->default('read');
            $table->string('box_role', 32)->nullable();
            $table->boolean('applies_to_subtree')->default(false);
            $table->string('state', 24)->default('desired');
            $table->string('box_collaboration_id', 64)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('source', 24)->default('manual');
            $table->timestamps();

            $table->unique(['policy_id', 'subject_type', 'subject_id'], 'box_access_grants_unique_subject_per_policy');
            $table->index(['policy_id', 'state']);
            $table->index('box_collaboration_id');
        });

        Schema::create('box_access_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_uuid')->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation_type', 32)->default('grant_apply');
            $table->string('status', 24)->default('pending');
            $table->foreignId('target_policy_id')->nullable()->constrained('box_access_policies')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['target_policy_id', 'status']);
        });

        Schema::create('box_access_operation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('box_access_operations')->cascadeOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('box_access_grants')->nullOnDelete();
            $table->string('box_item_type', 16)->default('folder');
            $table->string('box_item_id', 64);
            $table->string('action', 48);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 24)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['operation_id', 'status']);
        });

        Schema::create('box_access_drift_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('box_access_policies')->cascadeOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('box_access_grants')->nullOnDelete();
            $table->string('finding_type', 48);
            $table->string('severity', 16)->default('medium');
            $table->json('expected_state')->nullable();
            $table->json('actual_state')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'resolved_at']);
            $table->index(['severity', 'resolved_at']);
            $table->index(['grant_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_access_drift_findings');
        Schema::dropIfExists('box_access_operation_items');
        Schema::dropIfExists('box_access_operations');
        Schema::dropIfExists('box_access_grants');
        Schema::dropIfExists('box_access_policies');
    }
};

