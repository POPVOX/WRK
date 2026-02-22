<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('agent_type', 32)->default('specialist'); // specialist, project
            $table->string('specialty', 64)->nullable();
            $table->text('description')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->json('default_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_global')->default(true);
            $table->unsignedInteger('times_used')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_type', 'specialty', 'is_active']);
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('scope', 32)->default('specialist'); // specialist, project
            $table->string('specialty', 64)->nullable();
            $table->string('status', 32)->default('active'); // active, paused, archived
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('agent_templates')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('mission')->nullable();
            $table->longText('instructions')->nullable();
            $table->json('knowledge_sources')->nullable();
            $table->json('governance_tiers')->nullable();
            $table->string('autonomy_mode', 32)->default('tiered'); // tiered, propose_only
            $table->boolean('is_persistent')->default(true);
            $table->timestamp('last_directed_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'status']);
            $table->index(['specialty', 'status']);
            $table->index('project_id');
        });

        Schema::create('agent_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_create_specialist')->default(false);
            $table->boolean('can_create_project')->default(false);
            $table->string('project_scope', 32)->default('assigned'); // none, assigned, all, custom
            $table->json('allowed_project_ids')->nullable();
            $table->boolean('can_approve_medium_risk')->default(false);
            $table->boolean('can_approve_high_risk')->default(false);
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['project_scope', 'can_create_project']);
        });

        Schema::create('agent_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'created_at']);
            $table->unique(['agent_id', 'user_id']);
        });

        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('agent_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 32); // user, assistant, system
            $table->longText('content');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
            $table->index('role');
        });

        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('agent_threads')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('queued'); // queued, running, completed, failed
            $table->text('directive')->nullable();
            $table->text('result_summary')->nullable();
            $table->json('reasoning_chain')->nullable();
            $table->json('alternatives_considered')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('agent_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('agent_threads')->nullOnDelete();
            $table->string('suggestion_type', 64); // task, reminder, email_draft, project_create, subproject_create
            $table->string('title');
            $table->text('reasoning')->nullable();
            $table->json('payload')->nullable();
            $table->string('risk_level', 16)->default('medium'); // low, medium, high
            $table->string('approval_status', 32)->default('pending'); // pending, approved, modified, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'approval_status']);
            $table->index(['risk_level', 'approval_status']);
        });

        Schema::create('agent_suggestion_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->nullable()->constrained('agent_suggestions')->cascadeOnDelete();
            $table->foreignId('run_id')->nullable()->constrained('agent_runs')->cascadeOnDelete();
            $table->string('source_type', 64);
            $table->string('source_id', 64)->nullable();
            $table->string('source_title')->nullable();
            $table->text('excerpt')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_suggestion_sources');
        Schema::dropIfExists('agent_suggestions');
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('agent_messages');
        Schema::dropIfExists('agent_threads');
        Schema::dropIfExists('agent_permissions');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('agent_templates');
    }
};
