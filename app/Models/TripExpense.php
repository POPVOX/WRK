<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'category',
        'description',
        'expense_date',
        'amount',
        'currency',
        'amount_usd',
        'vendor',
        'receipt_number',
        'receipt_path',
        'receipt_original_name',
        'reimbursement_status',
        'reimbursement_submitted_date',
        'reimbursement_paid_date',
        'approved_by',
        'approved_at',
        'trip_sponsorship_id',
        'notes',
        'ai_extracted',
        'source_text',
        'source_url',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'reimbursement_submitted_date' => 'date',
        'reimbursement_paid_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'ai_extracted' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sponsorship(): BelongsTo
    {
        return $this->belongsTo(TripSponsorship::class, 'trip_sponsorship_id');
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'airfare' => '✈️',
            'lodging' => '🏨',
            'ground_transport' => '🚗',
            'meals' => '🍽️',
            'registration_fees' => '📋',
            'baggage_fees' => '🧳',
            'wifi_connectivity' => '📶',
            'tips_gratuities' => '💵',
            'visa_fees' => '🛂',
            'travel_insurance' => '🛡️',
            'office_supplies' => '📎',
            default => '📦',
        };
    }

    public function needsReimbursement(): bool
    {
        return in_array($this->reimbursement_status, ['pending', 'submitted']);
    }

    public static function getCategoryOptions(): array
    {
        return [
            'airfare' => 'Airfare',
            'lodging' => 'Lodging',
            'ground_transport' => 'Ground Transport',
            'meals' => 'Meals',
            'registration_fees' => 'Registration Fees',
            'baggage_fees' => 'Baggage Fees',
            'wifi_connectivity' => 'WiFi/Connectivity',
            'tips_gratuities' => 'Tips/Gratuities',
            'visa_fees' => 'Visa Fees',
            'travel_insurance' => 'Travel Insurance',
            'office_supplies' => 'Office Supplies',
            'other' => 'Other',
        ];
    }

    public static function getReimbursementStatusOptions(): array
    {
        return [
            'not_applicable' => 'N/A (Org Paid)',
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'denied' => 'Denied',
        ];
    }

    public static function getReimbursementStatusColors(): array
    {
        return [
            'not_applicable' => 'bg-gray-100 text-gray-600',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'submitted' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'paid' => 'bg-emerald-100 text-emerald-800',
            'denied' => 'bg-red-100 text-red-800',
        ];
    }
}
