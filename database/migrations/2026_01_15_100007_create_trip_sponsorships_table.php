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
        Schema::create('trip_sponsorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            $table->enum('type', [
                'full_sponsorship',
                'partial_sponsorship',
                'travel_only',
                'lodging_only',
                'registration_only',
                'honorarium'
            ]);
            
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // What's covered
            $table->boolean('covers_airfare')->default(false);
            $table->boolean('covers_lodging')->default(false);
            $table->boolean('covers_ground_transport')->default(false);
            $table->boolean('covers_meals')->default(false);
            $table->boolean('covers_registration')->default(false);
            $table->text('coverage_notes')->nullable();
            
            // Billing/Invoice info (Management only visibility)
            $table->text('billing_instructions')->nullable();
            $table->string('billing_contact_name')->nullable();
            $table->string('billing_contact_email')->nullable();
            $table->string('billing_contact_phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('invoice_reference')->nullable();
            $table->string('purchase_order_number')->nullable();
            
            // Payment tracking
            $table->enum('payment_status', [
                'pending',
                'invoiced',
                'partial_payment',
                'paid',
                'overdue'
            ])->default('pending');
            $table->date('invoice_sent_date')->nullable();
            $table->date('payment_due_date')->nullable();
            $table->date('payment_received_date')->nullable();
            $table->decimal('amount_received', 10, 2)->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_sponsorships');
    }
};
