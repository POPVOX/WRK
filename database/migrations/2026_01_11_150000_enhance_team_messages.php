<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add threading and screenshot support to team_messages
        Schema::table('team_messages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('team_messages')->onDelete('cascade');
            $table->string('screenshot_path', 500)->nullable()->after('content');
        });

        // Create reactions table
        Schema::create('team_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_message_id')->constrained('team_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji', 10); // Store the emoji itself
            $table->timestamps();

            $table->unique(['team_message_id', 'user_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_message_reactions');

        Schema::table('team_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'screenshot_path']);
        });
    }
};

