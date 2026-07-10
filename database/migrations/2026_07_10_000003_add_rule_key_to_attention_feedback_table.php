<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attention_feedback', function (Blueprint $table): void {
            $table->string('rule_key')->nullable()->after('source_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('attention_feedback', function (Blueprint $table): void {
            $table->dropColumn('rule_key');
        });
    }
};
