<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_slack_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slack_user_id', 64);
            $table->string('workspace_id', 64);
            $table->timestamps();

            $table->unique(['workspace_id', 'slack_user_id']);
            $table->unique(['user_id', 'workspace_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_slack_identities');
    }
};
