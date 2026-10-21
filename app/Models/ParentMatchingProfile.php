<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentMatchingProfile extends Model
{
    public const READINESS_REQUIREMENTS = [
        'physically_capable' => 'Physically capable of providing appropriate care',
        'financially_capable' => "Financially capable of meeting the child's needs",
        'willing_to_support_child' => "Willing to support the child's development and background",
    ];

    protected $fillable = [
        'user_id',
        'preferred_child_sex',
        'min_child_age',
        'max_child_age',
        'open_to_special_needs',
        'home_study_verified',
        'physically_capable',
        'financially_capable',
        'willing_to_support_child',
        'financial_capacity_score',
        'housing_score',
        'parenting_capacity_score',
        'matching_notes',
    ];

    protected $casts = [
        'open_to_special_needs' => 'boolean',
        'home_study_verified' => 'boolean',
        'physically_capable' => 'boolean',
        'financially_capable' => 'boolean',
        'willing_to_support_child' => 'boolean',
        'min_child_age' => 'integer',
        'max_child_age' => 'integer',
        'financial_capacity_score' => 'integer',
        'housing_score' => 'integer',
        'parenting_capacity_score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasCompletedReadinessChecklist(): bool
    {
        return collect(array_keys(self::READINESS_REQUIREMENTS))
            ->every(fn (string $field): bool => $this->{$field} === true);
    }

    public function acceptsChildSpecialNeeds(Child $child): bool
    {
        return ! $child->is_special_needs || $this->open_to_special_needs === true;
    }

    public function scopeReadyForMatching(Builder $query): Builder
    {
        foreach (array_keys(self::READINESS_REQUIREMENTS) as $field) {
            $query->where($field, true);
        }

        return $query;
    }
}
