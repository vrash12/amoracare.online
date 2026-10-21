<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Child extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'child_code',
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'sex',
        'date_of_birth',
        'place_of_birth',
        'current_location',
        'admission_date',
        'admission_reason',
        'case_status',
        'adoption_eligibility_status',
        'health_status',
        'educational_level',
        'school_name',
        'is_special_needs',
        'special_needs_categories',
        'background_summary',
        'remarks',
        'photo_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'is_special_needs' => 'boolean',
        'special_needs_categories' => 'array',
    ];

    public const CASE_STATUSES = [
        'in_care' => 'In Care',
        'available_for_adoption' => 'Available for Adoption',
        'under_matching' => 'Under Matching',
        'matched' => 'Matched',
        'adopted' => 'Adopted',
        'reintegrated' => 'Reintegrated',
        'inactive' => 'Inactive',
    ];

    public const ELIGIBILITY_STATUSES = [
        'not_assessed' => 'Not Assessed',
        'pending_documents' => 'Pending Documents',
        'eligible' => 'Eligible',
        'not_eligible' => 'Not Eligible',
    ];

    public const SPECIAL_NEEDS_CATEGORIES = [
        'developmental_intellectual_disability' => 'Developmental and intellectual disability',
        'behavioural_emotional_disorder' => 'Behavioural and emotional disorder',
        'sensory_impairment' => 'Sensory impairment',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->implode(' ');
    }

    public function getCaseStatusLabelAttribute(): string
    {
        return self::CASE_STATUSES[$this->case_status] ?? $this->case_status;
    }

    public function getEligibilityStatusLabelAttribute(): string
    {
        return self::ELIGIBILITY_STATUSES[$this->adoption_eligibility_status] ?? $this->adoption_eligibility_status;
    }

    public function adoptionCases(): HasMany
    {
        return $this->hasMany(AdoptionCase::class);
    }

    public function matchingResults(): HasMany
    {
        return $this->hasMany(AdoptionMatchingResult::class);
    }
}
