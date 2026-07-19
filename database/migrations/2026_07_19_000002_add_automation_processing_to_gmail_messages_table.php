<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->timestamp('automation_processed_at')->nullable()->index()->after('labels');
            $table->string('automation_disposition', 64)->nullable()->after('automation_processed_at');
            $table->text('automation_error')->nullable()->after('automation_disposition');
        });
    }

    public function down(): void
    {
        Schema::table('gmail_messages', function (Blueprint $table) {
            $table->dropColumn([
                'automation_processed_at',
                'automation_disposition',
                'automation_error',
            ]);
        });
    }
};
