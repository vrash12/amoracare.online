<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdoptionCase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'case_code',
        'child_id',
        'prospective_parent_id',
        'assigned_social_worker_id',
        'case_type',
        'status',
        'priority',
        'opened_at',
        'target_completion_date',
        'closed_at',
        'summary',
        'confidential_notes',

        // RACCO review fields, keep these if you already added the RACCO workflow migration.
        'racco_review_status',
        'submitted_to_racco_by',
        'submitted_to_racco_at',
        'racco_decided_by',
        'racco_decided_at',
        'racco_decision_remarks',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opened_at' => 'date',
        'target_completion_date' => 'date',
        'closed_at' => 'date',
        'submitted_to_racco_at' => 'datetime',
        'racco_decided_at' => 'datetime',
    ];

    public const CASE_TYPES = [
        'domestic_adoption' => 'Domestic Adoption',
        'relative_adoption' => 'Relative Adoption',
        'foster_to_adopt' => 'Foster to Adopt',
        'step_parent_adoption' => 'Step-Parent Adoption',
        'adult_adoption' => 'Adult Adoption',
        'special_case' => 'Special Case',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'document_collection' => 'Document Collection',
        'assessment' => 'Assessment',
        'matching_review' => 'Matching Review',
        'pre_placement' => 'Pre-Placement',
        'placement_supervision' => 'Placement Supervision',
        'court_process' => 'Court / Administrative Petition Process',
        'finalized' => 'Finalized',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const RACCO_REVIEW_STATUSES = [
        'not_submitted' => 'Not Submitted',
        'submitted' => 'Submitted to RACCO',
        'under_review' => 'Under RACCO Review',
        'approved' => 'Approved by RACCO',
        'changes_requested' => 'Changes Requested',
        'returned' => 'Returned',
    ];

    public const DEFAULT_DOCUMENTS = [
        /*
        |--------------------------------------------------------------------------
        | Parent Applicant Requirements
        |--------------------------------------------------------------------------
        */

        [
            'document_name' => 'Undertaking and Application Form',
            'document_type' => 'undertaking_application_form',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'PSA Birth Certificate of Applicant/s',
            'document_type' => 'applicant_birth_certificate',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Marriage Certificate / CENOMAR / Civil Status Document',
            'document_type' => 'civil_status_document',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Written Consent from Appropriate Person/s',
            'document_type' => 'written_consent',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Medical Certification of Applicant/s',
            'document_type' => 'applicant_medical_certification',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Psychological Evaluation Report of Applicant/s',
            'document_type' => 'applicant_psychological_evaluation',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'NBI / Police / Court Clearance',
            'document_type' => 'clearance',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Latest ITR / Financial Capacity Document',
            'document_type' => 'financial_capacity_document',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Three Character Reference Letters',
            'document_type' => 'character_reference_letters',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Photos of Applicant/s, Immediate Family, and Home',
            'document_type' => 'applicant_family_home_photos',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Certificate of Completion / Attendance in Adoption Training',
            'document_type' => 'adoption_training_certificate',
            'requirement_scope' => 'parent',
        ],
        [
            'document_name' => 'Adoption Decree, if with Previous Adopted Child',
            'document_type' => 'previous_adoption_decree',
            'requirement_scope' => 'parent',
        ],

        /*
        |--------------------------------------------------------------------------
        | Child Legal Availability Requirements
        |--------------------------------------------------------------------------
        */

        [
            'document_name' => 'Child Case Study Report',
            'document_type' => 'child_case_study_report',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'PSA / LCR Birth Certificate or Foundling Certificate',
            'document_type' => 'child_birth_or_foundling_certificate',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Original CDCLAA',
            'document_type' => 'cdclaa',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Proof of Biological Parent Search',
            'document_type' => 'biological_parent_search_proof',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Media Certification / Publication Proof',
            'document_type' => 'media_certification_publication_proof',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Police Report or Barangay Certification',
            'document_type' => 'police_barangay_certification',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Recent Photo of Child',
            'document_type' => 'recent_child_photo',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Photo of Child upon Admission / Abandonment',
            'document_type' => 'child_admission_photo',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Deed of Voluntary Commitment, if applicable',
            'document_type' => 'deed_of_voluntary_commitment',
            'requirement_scope' => 'child',
        ],
        [
            'document_name' => 'Order of Involuntary Commitment, if applicable',
            'document_type' => 'involuntary_commitment_order',
            'requirement_scope' => 'child',
        ],

        /*
        |--------------------------------------------------------------------------
        | Petition and Case Requirements
        |--------------------------------------------------------------------------
        */

        [
            'document_name' => 'Notarized Petition for Adoption',
            'document_type' => 'notarized_petition_for_adoption',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Social Case Study Report',
            'document_type' => 'social_case_study_report',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Medical Evaluation of Child and PAP/s',
            'document_type' => 'child_and_parent_medical_evaluation',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Psychological Evaluation of Child, if 5 Years Old and Above',
            'document_type' => 'child_psychological_evaluation',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Child Care Plan with at Least Three Temporary Custodians',
            'document_type' => 'child_care_plan',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Written Consent of Child, if 10 Years Old and Above',
            'document_type' => 'child_written_consent',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Written Consent of Marital / Adopted Children of PAP/s, if applicable',
            'document_type' => 'children_of_paps_written_consent',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Written Consent of Non-Marital Children of PAP/s, if applicable',
            'document_type' => 'non_marital_children_written_consent',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Death Certificate of Child Biological Parent/s, if applicable',
            'document_type' => 'biological_parent_death_certificate',
            'requirement_scope' => 'case',
        ],

        /*
        |--------------------------------------------------------------------------
        | Matching, Placement, and Finalization Requirements
        |--------------------------------------------------------------------------
        */

        [
            'document_name' => 'Certificate of Matching',
            'document_type' => 'certificate_of_matching',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Placement Acceptance / Decline Decision',
            'document_type' => 'placement_acceptance_decline_decision',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Pre-Adoption Placement Authority',
            'document_type' => 'papa',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Supervised Trial Custody Report',
            'document_type' => 'supervised_trial_custody_report',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Final Supervisory Report',
            'document_type' => 'final_supervisory_report',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Updated Social Case Study Report',
            'document_type' => 'updated_social_case_study_report',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Order of Adoption',
            'document_type' => 'order_of_adoption',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Certificate of Finality',
            'document_type' => 'certificate_of_finality',
            'requirement_scope' => 'case',
        ],
        [
            'document_name' => 'Proof of Registration with Local Civil Registrar',
            'document_type' => 'local_civil_registrar_registration_proof',
            'requirement_scope' => 'case',
        ],
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function prospectiveParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prospective_parent_id');
    }

    public function assignedSocialWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_social_worker_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AdoptionCaseDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AdoptionCaseNote::class);
    }

    public function reviewerAccesses(): HasMany
    {
        return $this->hasMany(ExternalReviewerCaseAccess::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submittedToRaccoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_to_racco_by');
    }

    public function raccoDecisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'racco_decided_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function getCaseTypeLabelAttribute(): string
    {
        return self::CASE_TYPES[$this->case_type] ?? ucwords(str_replace('_', ' ', (string) $this->case_type));
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucwords(str_replace('_', ' ', (string) $this->priority));
    }

    public function getRaccoReviewStatusLabelAttribute(): string
    {
        return self::RACCO_REVIEW_STATUSES[$this->racco_review_status ?? 'not_submitted']
            ?? ucwords(str_replace('_', ' ', (string) $this->racco_review_status));
    }

    public function getDocumentProgressAttribute(): string
    {
        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->get();

        $total = $documents->count();

        if ($total === 0) {
            return '0/0';
        }

        $verified = $documents
            ->where('status', 'verified')
            ->count();

        return "{$verified}/{$total}";
    }

    public function getDocumentProgressPercentAttribute(): int
    {
        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->get();

        $total = $documents->count();

        if ($total === 0) {
            return 0;
        }

        $verified = $documents
            ->where('status', 'verified')
            ->count();

        return (int) round(($verified / $total) * 100);
    }

    public function getParentDocumentProgressAttribute(): string
    {
        return $this->documentProgressByScope('parent');
    }

    public function getChildDocumentProgressAttribute(): string
    {
        return $this->documentProgressByScope('child');
    }

    public function getCaseDocumentProgressAttribute(): string
    {
        return $this->documentProgressByScope('case');
    }

    private function documentProgressByScope(string $scope): string
    {
        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->get();

        $scopedDocuments = $documents->where('requirement_scope', $scope);

        $total = $scopedDocuments->count();

        if ($total === 0) {
            return '0/0';
        }

        $verified = $scopedDocuments
            ->where('status', 'verified')
            ->count();

        return "{$verified}/{$total}";
    }
}