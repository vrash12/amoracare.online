<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentMatchingProfile extends Model
{
    protected $fillable = [
        'user_id',
        'preferred_child_sex',
        'min_child_age',
        'max_child_age',
        'open_to_special_needs',
        'home_study_verified',
        'financial_capacity_score',
        'housing_score',
        'parenting_capacity_score',
        'matching_notes',
    ];

    protected $casts = [
        'open_to_special_needs' => 'boolean',
        'home_study_verified' => 'boolean',
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
}