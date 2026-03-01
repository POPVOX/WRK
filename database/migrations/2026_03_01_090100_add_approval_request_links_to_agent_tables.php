<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trip_agent_actions')) {
            Schema::table('trip_agent_actions', function (Blueprint $table) {
                if (! Schema::hasColumn('trip_agent_actions', 'approval_request_id')) {
                    $table->foreignId('approval_request_id')
                        ->nullable()
                        ->after('requested_by')
                        ->constrained('approval_requests')
                        ->nullOnDelete();
                    $table->index('approval_request_id');
                }
            });
        }

        if (Schema::hasTable('agent_suggestions')) {
            Schema::table('agent_suggestions', function (Blueprint $table) {
                if (! Schema::hasColumn('agent_suggestions', 'approval_request_id')) {
                    $table->foreignId('approval_request_id')
                        ->nullable()
                        ->after('approval_status')
                        ->constrained('approval_requests')
                        ->nullOnDelete();
                    $table->index('approval_request_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agent_suggestions') && Schema::hasColumn('agent_suggestions', 'approval_request_id')) {
            Schema::table('agent_suggestions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approval_request_id');
            });
        }

        if (Schema::hasTable('trip_agent_actions') && Schema::hasColumn('trip_agent_actions', 'approval_request_id')) {
            Schema::table('trip_agent_actions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approval_request_id');
            });
        }
    }
};
