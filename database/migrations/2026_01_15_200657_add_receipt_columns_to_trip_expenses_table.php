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
        Schema::table('trip_expenses', function (Blueprint $table) {
            // Receipt file storage
            $table->string('receipt_path')->nullable()->after('receipt_number');
            $table->string('receipt_original_name')->nullable()->after('receipt_path');

            // AI extraction tracking
            $table->boolean('ai_extracted')->default(false)->after('notes');
            $table->text('source_text')->nullable()->after('ai_extracted');
            $table->string('source_url')->nullable()->after('source_text');

            // Approval workflow
            $table->foreignId('approved_by')->nullable()->after('reimbursement_paid_date')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_expenses', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'receipt_path',
                'receipt_original_name',
                'ai_extracted',
                'source_text',
                'source_url',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
