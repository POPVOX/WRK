<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('followup_action_id')->nullable()->constrained('actions')->nullOnDelete();
            $table->string('source', 64)->default('workspace_companion');
            $table->string('status', 32)->default('draft'); // draft, escalated, resolved
            $table->text('summary');
            $table->text('raw_context')->nullable();
            $table->boolean('share_raw_with_management')->default(false);
            $table->string('escalation_reason', 64)->nullable(); // explicit_request, repeat_threshold
            $table->unsignedInteger('window_signal_count')->default(1);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('digest_included_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['manager_user_id', 'status', 'escalated_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_signals');
    }
};

