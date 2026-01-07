<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporting_requirements', function (Blueprint $table) {
            $table->foreignId('source_document_id')->nullable()->after('grant_id')
                ->constrained('grant_documents')->nullOnDelete();
            $table->text('source_quote')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('reporting_requirements', function (Blueprint $table) {
            $table->dropForeign(['source_document_id']);
            $table->dropColumn(['source_document_id', 'source_quote']);
        });
    }
};
