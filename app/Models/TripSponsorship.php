<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripSponsorship extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'organization_id',
        'type',
        'description',
        'amount',
        'currency',
        'covers_airfare',
        'covers_lodging',
        'covers_ground_transport',
        'covers_meals',
        'covers_registration',
        'coverage_notes',
        'billing_instructions',
        'billing_contact_name',
        'billing_contact_email',
        'billing_contact_phone',
        'billing_address',
        'invoice_reference',
        'purchase_order_number',
        'payment_status',
        'invoice_sent_date',
        'payment_due_date',
        'payment_received_date',
        'amount_received',
        'notes',
        // Agreement parsing
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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'total_consulting_fees' => 'decimal:2',
        'total_reimbursable' => 'decimal:2',
        'covers_airfare' => 'boolean',
        'covers_lodging' => 'boolean',
        'covers_ground_transport' => 'boolean',
        'covers_meals' => 'boolean',
        'covers_registration' => 'boolean',
        'invoice_sent_date' => 'date',
        'payment_due_date' => 'date',
        'payment_received_date' => 'date',
        'invoice_deadline' => 'date',
        'terms_extracted_at' => 'datetime',
        'extracted_terms' => 'array',
        'line_items' => 'array',
        'deliverables' => 'array',
        'covered_travelers' => 'array',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TripExpense::class);
    }

    public function getCoverageListAttribute(): array
    {
        $coverage = [];

        if ($this->covers_airfare) {
            $coverage[] = 'Airfare';
        }
        if ($this->covers_lodging) {
            $coverage[] = 'Lodging';
        }
        if ($this->covers_ground_transport) {
            $coverage[] = 'Ground Transport';
        }
        if ($this->covers_meals) {
            $coverage[] = 'Meals';
        }
        if ($this->covers_registration) {
            $coverage[] = 'Registration';
        }

        return $coverage;
    }

    public function getAmountOutstandingAttribute(): float
    {
        return ($this->amount ?? 0) - ($this->amount_received ?? 0);
    }

    public function isOverdue(): bool
    {
        if (! $this->payment_due_date) {
            return false;
        }

        return $this->payment_status !== 'paid' && $this->payment_due_date->lt(today());
    }

    public static function getTypeOptions(): array
    {
        return [
            'full_sponsorship' => 'Full Sponsorship',
            'partial_sponsorship' => 'Partial Sponsorship',
            'travel_only' => 'Travel Only',
            'lodging_only' => 'Lodging Only',
            'registration_only' => 'Registration Only',
            'honorarium' => 'Honorarium',
        ];
    }

    public static function getPaymentStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'invoiced' => 'Invoiced',
            'partial_payment' => 'Partial Payment',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
        ];
    }

    public static function getPaymentStatusColors(): array
    {
        return [
            'pending' => 'bg-gray-100 text-gray-600',
            'invoiced' => 'bg-blue-100 text-blue-800',
            'partial_payment' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TripSponsorshipDocument::class);
    }

    /**
     * Get deliverables completion status
     */
    public function getDeliverablesProgressAttribute(): array
    {
        $deliverables = $this->deliverables ?? [];
        $total = count($deliverables);
        $completed = collect($deliverables)->where('is_completed', true)->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $total - $completed,
            'percent' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * Check if all deliverables are complete
     */
    public function allDeliverablesComplete(): bool
    {
        $progress = $this->deliverables_progress;

        return $progress['total'] > 0 && $progress['completed'] === $progress['total'];
    }

    /**
     * Mark a deliverable as complete
     */
    public function markDeliverableComplete(int $index): void
    {
        $deliverables = $this->deliverables ?? [];
        
        if (isset($deliverables[$index])) {
            $deliverables[$index]['is_completed'] = true;
            $deliverables[$index]['completed_at'] = now()->toISOString();
            $this->deliverables = $deliverables;
            $this->save();
        }
    }

    /**
     * Mark a deliverable as incomplete
     */
    public function markDeliverableIncomplete(int $index): void
    {
        $deliverables = $this->deliverables ?? [];
        
        if (isset($deliverables[$index])) {
            $deliverables[$index]['is_completed'] = false;
            $deliverables[$index]['completed_at'] = null;
            $this->deliverables = $deliverables;
            $this->save();
        }
    }

    /**
     * Check if ready to invoice (all deliverables complete)
     */
    public function isReadyToInvoice(): bool
    {
        if ($this->payment_status !== 'pending') {
            return false;
        }

        return $this->allDeliverablesComplete() || empty($this->deliverables);
    }

    /**
     * Get line items by category
     */
    public function getLineItemsByCategory(): array
    {
        $items = $this->line_items ?? [];
        $grouped = [];

        foreach ($items as $item) {
            $category = $item['category'] ?? 'other';
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $item;
        }

        return $grouped;
    }

    /**
     * Get the currency symbol
     */
    public function getCurrencySymbolAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        return $symbols[$this->currency ?? 'USD'] ?? $this->currency ?? '$';
    }
}
