<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalReviewerCaseAccess extends Model
{
    protected $fillable = [
        'reviewer_id',
        'adoption_case_id',
        'access_status',
        'can_view_summary',
        'can_view_document_status',
        'can_submit_notes',
        'can_make_decision',
        'authorized_by',
        'authorized_at',
        'expires_at',
        'remarks',
    ];

    protected $casts = [
        'can_view_summary' => 'boolean',
        'can_view_document_status' => 'boolean',
        'can_submit_notes' => 'boolean',
        'can_make_decision' => 'boolean',
        'authorized_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function adoptionCase(): BelongsTo
    {
        return $this->belongsTo(AdoptionCase::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->access_status === 'active' && !$this->is_expired;
    }
}