<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_sponsorships', function (Blueprint $table) {
            // Agreement source
            $table->text('agreement_text')->nullable()->after('notes');
            $table->string('agreement_file_path')->nullable()->after('agreement_text');
            $table->string('agreement_file_name')->nullable()->after('agreement_file_path');
            
            // AI-extracted terms (JSON)
            $table->json('extracted_terms')->nullable()->after('agreement_file_name');
            $table->timestamp('terms_extracted_at')->nullable()->after('extracted_terms');
            
            // Line items breakdown (JSON array)
            // Each item: { description, amount, currency, category, is_reimbursable, notes }
            $table->json('line_items')->nullable()->after('terms_extracted_at');
            
            // Financial details
            $table->decimal('total_consulting_fees', 12, 2)->nullable()->after('line_items');
            $table->decimal('total_reimbursable', 12, 2)->nullable()->after('total_consulting_fees');
            $table->string('exchange_rate_note')->nullable()->after('total_reimbursable');
            
            // Payment terms
            $table->string('payment_terms')->nullable()->after('exchange_rate_note'); // e.g., "Net 30"
            $table->date('invoice_deadline')->nullable()->after('payment_terms');
            
            // Deliverables required for payment
            $table->json('deliverables')->nullable()->after('invoice_deadline');
            // Each: { description, is_completed, completed_at, notes }
            
            // Who needs to be covered
            $table->json('covered_travelers')->nullable()->after('deliverables');
            // Array of traveler names/info from agreement
        });
        
        // Create sponsorship documents table for multiple uploads
        Schema::create('trip_sponsorship_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_sponsorship_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // contract, invoice, receipt, etc.
            $table->text('extracted_text')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_sponsorship_documents');
        
        Schema::table('trip_sponsorships', function (Blueprint $table) {
            $table->dropColumn([
                'agreement_text',
                'agreement_file_path',
                'agreement_file_name',
                'extracted_terms',
                'terms_extracted_at',
                'line_items',
                'total_consulting_fees',
                'total_reimbursable',
                'exchange_rate_note',
                'payment_terms',
                'invoice_deadline',
                'deliverables',
                'covered_travelers',
            ]);
        });
    }
};
