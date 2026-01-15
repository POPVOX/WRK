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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Trip Classification
            $table->enum('type', [
                'conference_event',
                'funder_meeting', 
                'site_visit',
                'advocacy_hill_day',
                'training',
                'partner_delegation',
                'board_meeting',
                'speaking_engagement',
                'research',
                'other'
            ])->default('other');
            
            // Status
            $table->enum('status', [
                'planning',
                'booked',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('planning');
            
            // Dates (overall trip window)
            $table->date('start_date');
            $table->date('end_date');
            
            // Primary Destination (for display/filtering)
            $table->string('primary_destination_city');
            $table->string('primary_destination_country', 2); // ISO code
            $table->string('primary_destination_region')->nullable();
            
            // Associations
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Policy Compliance
            $table->enum('risk_level', ['standard', 'moderate', 'high', 'prohibited'])->nullable();
            $table->boolean('step_registration_required')->default(false);
            $table->boolean('step_registration_completed')->default(false);
            $table->boolean('travel_insurance_required')->default(false);
            $table->boolean('travel_insurance_confirmed')->default(false);
            $table->boolean('approval_required')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Partner Organization (for delegations)
            $table->foreignId('partner_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('partner_program_name')->nullable();
            
            // Post-Trip
            $table->text('debrief_notes')->nullable();
            $table->text('outcomes')->nullable();
            
            // Metadata
            $table->boolean('is_template')->default(false);
            $table->foreignId('created_from_template_id')->nullable()->constrained('trips')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['start_date', 'end_date']);
            $table->index('status');
            $table->index('primary_destination_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
