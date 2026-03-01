<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 120);
            $table->string('risk_level', 16)->default('medium');
            $table->string('approval_status', 32)->default('pending'); // pending, approved, rejected, cancelled
            $table->string('title')->nullable();
            $table->text('rationale')->nullable();
            $table->json('context')->nullable();
            $table->string('dedupe_key', 64)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['action_type', 'approval_status']);
            $table->index(['requested_by', 'approval_status']);
            $table->index('dedupe_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
