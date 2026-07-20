<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donation_code',
        'donor_id',
        'donation_type',
        'donation_date',
        'purpose',
        'allocation_notes',
        'cash_amount',
        'payment_method',
        'reference_number',
        'receipt_number',
        'acknowledgment_status',
        'acknowledgment_date',
        'status',
        'remarks',
        'encoded_by',
        'updated_by',
    ];

    protected $casts = [
        'donation_date' => 'date',
        'acknowledgment_date' => 'date',
        'cash_amount' => 'decimal:2',
    ];

    public const TYPES = [
        'cash' => 'Cash Donation',
        'in_kind' => 'In-Kind / Material Donation',
        'mixed' => 'Cash and In-Kind Donation',
    ];

    public const PURPOSES = [
        'general_support' => 'General Support',
        'food' => 'Food',
        'education' => 'Education',
        'medical' => 'Medical',
        'clothing' => 'Clothing',
        'shelter' => 'Shelter',
        'operations' => 'Operations',
        'adoption_program' => 'Adoption Program',
        'other' => 'Other',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'gcash' => 'GCash',
        'check' => 'Check',
        'other' => 'Other',
    ];

    public const ACKNOWLEDGMENT_STATUSES = [
        'pending' => 'Pending',
        'issued' => 'Issued',
        'not_required' => 'Not Required',
    ];

    public const STATUSES = [
        'recorded' => 'Recorded',
        'verified' => 'Verified',
        'cancelled' => 'Cancelled',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DonationItem::class);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDonationTypeLabelAttribute(): string
    {
        return self::TYPES[$this->donation_type] ?? $this->donation_type;
    }

    public function getPurposeLabelAttribute(): string
    {
        return self::PURPOSES[$this->purpose] ?? $this->purpose;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getAcknowledgmentStatusLabelAttribute(): string
    {
        return self::ACKNOWLEDGMENT_STATUSES[$this->acknowledgment_status] ?? $this->acknowledgment_status;
    }

    public function getEstimatedInKindTotalAttribute(): float
    {
        return (float) $this->items->sum('estimated_total_value');
    }
}