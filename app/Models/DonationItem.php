<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationItem extends Model
{
    protected $fillable = [
        'donation_id',
        'item_name',
        'item_category',
        'description',
        'quantity',
        'unit',
        'estimated_unit_value',
        'estimated_total_value',
        'condition_status',
        'storage_location',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_value' => 'decimal:2',
        'estimated_total_value' => 'decimal:2',
    ];

    public const CATEGORIES = [
        'food' => 'Food',
        'clothing' => 'Clothing',
        'medicine' => 'Medicine',
        'school_supplies' => 'School Supplies',
        'toys' => 'Toys',
        'hygiene' => 'Hygiene Supplies',
        'furniture' => 'Furniture',
        'equipment' => 'Equipment',
        'other' => 'Other',
    ];

    public const CONDITIONS = [
        'new' => 'New',
        'used_good' => 'Used - Good',
        'used_fair' => 'Used - Fair',
        'needs_checking' => 'Needs Checking',
    ];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function getItemCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->item_category] ?? $this->item_category;
    }

    public function getConditionStatusLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition_status] ?? $this->condition_status;
    }
}