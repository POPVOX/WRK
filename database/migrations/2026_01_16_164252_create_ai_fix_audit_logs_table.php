<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_fix_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('ai_fix_proposals')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action'); // requested, generated, approved, deployed, rejected, failed
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'action']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_fix_audit_logs');
    }
};
