<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donor_code',
        'name',
        'donor_type',
        'email',
        'phone_number',
        'address',
        'notes',
    ];

    public const TYPES = [
        'individual' => 'Individual',
        'organization' => 'Organization',
        'anonymous' => 'Anonymous',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function getDonorTypeLabelAttribute(): string
    {
        return self::TYPES[$this->donor_type] ?? $this->donor_type;
    }
}