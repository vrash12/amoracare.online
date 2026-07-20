<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdoptionMatchingResult extends Model
{
    protected $fillable = [
        'adoption_matching_run_id',
        'child_id',
        'prospective_parent_id',
        'child_score',
        'parent_score',
        'rank_for_child',
        'rank_for_parent',
        'status',
        'explanation',
        'created_by',
    ];

    protected $casts = [
        'explanation' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AdoptionMatchingRun::class, 'adoption_matching_run_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function prospectiveParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prospective_parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
{
    return [
        'explanation' => 'array',
        'child_score' => 'decimal:2',
        'parent_score' => 'decimal:2',
    ];
}
}