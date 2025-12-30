<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                if (!Schema::hasColumn('people', 'status')) {
                    $table->string('status')->nullable()->after('notes'); // lead, prospect, active, partner, inactive
                }
                if (!Schema::hasColumn('people', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable()->after('status');
                    $table->index('owner_id');
                }
                if (!Schema::hasColumn('people', 'source')) {
                    $table->string('source')->nullable()->after('owner_id');
                }
                if (!Schema::hasColumn('people', 'tags')) {
                    $table->json('tags')->nullable()->after('source');
                }
                if (!Schema::hasColumn('people', 'last_contacted_at')) {
                    $table->timestamp('last_contacted_at')->nullable()->after('tags');
                }
                if (!Schema::hasColumn('people', 'next_action_at')) {
                    $table->timestamp('next_action_at')->nullable()->after('last_contacted_at');
                }
                if (!Schema::hasColumn('people', 'next_action_note')) {
                    $table->text('next_action_note')->nullable()->after('next_action_at');
                }
                if (!Schema::hasColumn('people', 'score')) {
                    $table->integer('score')->default(0)->after('next_action_note');
                }

                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                foreach ([
                    'status',
                    'owner_id',
                    'source',
                    'tags',
                    'last_contacted_at',
                    'next_action_at',
                    'next_action_note',
                    'score',
                ] as $col) {
                    if (Schema::hasColumn('people', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
