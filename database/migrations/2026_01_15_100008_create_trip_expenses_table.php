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
        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who incurred
            
            $table->enum('category', [
                'airfare',
                'lodging',
                'ground_transport',
                'meals',
                'registration_fees',
                'baggage_fees',
                'wifi_connectivity',
                'tips_gratuities',
                'visa_fees',
                'travel_insurance',
                'office_supplies',
                'other'
            ]);
            
            $table->string('description');
            $table->date('expense_date');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount_usd', 10, 2)->nullable(); // Converted amount
            
            $table->string('vendor')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Reimbursement tracking (internal - from POPVOX to employee)
            $table->enum('reimbursement_status', [
                'not_applicable',
                'pending',
                'submitted',
                'approved',
                'paid',
                'denied'
            ])->default('not_applicable');
            $table->date('reimbursement_submitted_date')->nullable();
            $table->date('reimbursement_paid_date')->nullable();
            
            // Link to sponsorship if externally funded
            $table->foreignId('trip_sponsorship_id')->nullable()->constrained()->nullOnDelete();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('reimbursement_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_expenses');
    }
};
