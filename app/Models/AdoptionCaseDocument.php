<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdoptionCaseDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'adoption_case_id',
        'document_name',
        'document_type',
        'requirement_scope',
        'status',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'expiry_date',
        'remarks',
        'verified_by',
        'verified_at',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'not_required' => 'Not Required',
    ];

    public const SCOPES = [
        'child' => 'Child Requirement',
        'parent' => 'Parent Requirement',
        'case' => 'Case Requirement',
    ];

    public function adoptionCase(): BelongsTo
    {
        return $this->belongsTo(AdoptionCase::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getScopeLabelAttribute(): string
    {
        return self::SCOPES[$this->requirement_scope] ?? $this->requirement_scope;
    }
}