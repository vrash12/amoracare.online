<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdoptionCaseNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'adoption_case_id',
        'note_type',
        'visibility',
        'title',
        'body',
        'created_by',
        'updated_by',
    ];

    public const NOTE_TYPES = [
        'general' => 'General',
        'status_update' => 'Status Update',
        'document_review' => 'Document Review',
        'matching_review' => 'Matching Review',
        'placement' => 'Placement',
        'legal' => 'Legal',
        'external_review' => 'External Review',
    ];

    public const VISIBILITIES = [
        'internal' => 'Internal Staff Only',
        'reviewer_summary' => 'External Reviewer Summary',
        'parent_update' => 'Parent Status Update',
    ];

    public function adoptionCase(): BelongsTo
    {
        return $this->belongsTo(AdoptionCase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNoteTypeLabelAttribute(): string
    {
        return self::NOTE_TYPES[$this->note_type] ?? $this->note_type;
    }

    public function getVisibilityLabelAttribute(): string
    {
        return self::VISIBILITIES[$this->visibility] ?? $this->visibility;
    }
}