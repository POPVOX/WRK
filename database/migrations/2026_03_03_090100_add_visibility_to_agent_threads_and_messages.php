<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_threads', 'visibility')) {
                $table->string('visibility', 16)->default('private')->after('title');
                $table->index(['agent_id', 'visibility']);
            }
        });

        Schema::table('agent_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_messages', 'visibility')) {
                $table->string('visibility', 16)->nullable()->after('meta');
                $table->index(['thread_id', 'visibility']);
            }
        });

        if (Schema::hasColumn('agent_threads', 'visibility')) {
            DB::table('agent_threads')
                ->whereNull('visibility')
                ->update(['visibility' => 'private']);
        }
    }

    public function down(): void
    {
        Schema::table('agent_messages', function (Blueprint $table) {
            if (Schema::hasColumn('agent_messages', 'visibility')) {
                $table->dropIndex('agent_messages_thread_id_visibility_index');
                $table->dropColumn('visibility');
            }
        });

        Schema::table('agent_threads', function (Blueprint $table) {
            if (Schema::hasColumn('agent_threads', 'visibility')) {
                $table->dropIndex('agent_threads_agent_id_visibility_index');
                $table->dropColumn('visibility');
            }
        });
    }
};
