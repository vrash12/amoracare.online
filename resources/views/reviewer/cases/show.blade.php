@extends('layouts.dashboard', ['title' => 'Authorized Case Summary'])

@section('content')
@php
    $documents = $adoptionCase->documents
        ->sortBy('document_name')
        ->values();

    $documentGroups = $documentGroups ?? [
        'parent' => [
            'label' => 'Parent Applicant Requirements',
            'description' => 'Documents submitted or required from the prospective adoptive parent.',
            'documents' => $documents->where('requirement_scope', 'parent')->values(),
        ],
        'child' => [
            'label' => 'Child Legal Availability Requirements',
            'description' => 'Documents supporting the child record and legal availability for adoption.',
            'documents' => $documents->where('requirement_scope', 'child')->values(),
        ],
        'case' => [
            'label' => 'Case, Petition, Placement, and Finalization Requirements',
            'description' => 'Documents related to petition, placement, supervision, and finalization.',
            'documents' => $documents->where('requirement_scope', 'case')->values(),
        ],
    ];

    $documentSummary = $documentSummary ?? [
        'total' => $documents->count(),
        'verified' => $documents->where('status', 'verified')->count(),
        'pending' => $documents->where('status', 'pending')->count(),
        'submitted' => $documents->where('status', 'submitted')->count(),
        'under_review' => $documents->where('status', 'under_review')->count(),
        'rejected' => $documents->where('status', 'rejected')->count(),
        'expired' => $documents->where('status', 'expired')->count(),
        'progress_percent' => $documents->count() > 0
            ? round(($documents->where('status', 'verified')->count() / $documents->count()) * 100)
            : 0,
    ];

    $safeProgress = max(
        0,
        min(100, (int) ($documentSummary['progress_percent'] ?? 0))
    );

    $attentionDocuments = $documents
        ->whereIn('status', ['pending', 'rejected', 'expired'])
        ->values();

    $submittedForReviewCount = $documents
        ->whereIn('status', ['submitted', 'under_review'])
        ->count();

    $statusMeta = [
        'pending' => [
            'label' => 'Pending',
            'class' => 'is-pending',
            'icon' => 'bi-clock',
            'description' => 'No submitted file is currently ready for reviewer action.',
        ],
        'submitted' => [
            'label' => 'Submitted',
            'class' => 'is-submitted',
            'icon' => 'bi-cloud-check',
            'description' => 'A file has been submitted and is ready for review.',
        ],
        'under_review' => [
            'label' => 'Under review',
            'class' => 'is-review',
            'icon' => 'bi-search',
            'description' => 'This requirement is currently being reviewed.',
        ],
        'verified' => [
            'label' => 'Verified',
            'class' => 'is-verified',
            'icon' => 'bi-patch-check',
            'description' => 'The submitted file has been accepted.',
        ],
        'rejected' => [
            'label' => 'Needs revision',
            'class' => 'is-rejected',
            'icon' => 'bi-exclamation-triangle',
            'description' => 'The submitted file was returned for correction or resubmission.',
        ],
        'expired' => [
            'label' => 'Expired',
            'class' => 'is-expired',
            'icon' => 'bi-calendar-x',
            'description' => 'The requirement may need a renewed or updated file.',
        ],
    ];

    $scopeMeta = [
        'parent' => [
            'label' => 'Parent',
            'class' => 'is-parent',
            'icon' => 'bi-people',
        ],
        'child' => [
            'label' => 'Child legal',
            'class' => 'is-child',
            'icon' => 'bi-shield-check',
        ],
        'case' => [
            'label' => 'Case',
            'class' => 'is-case',
            'icon' => 'bi-folder2-open',
        ],
    ];

    $caseStatus = $adoptionCase->status ?? 'unknown';

    $caseStatusMeta = match ($caseStatus) {
        'finalized', 'closed' => [
            'class' => 'is-green',
            'icon' => 'bi-patch-check',
            'message' => 'This case has reached a completed or closed workflow stage.',
        ],
        'cancelled', 'rejected' => [
            'class' => 'is-red',
            'icon' => 'bi-exclamation-octagon',
            'message' => 'This case is not currently proceeding through the standard workflow.',
        ],
        'matching_review', 'matching' => [
            'class' => 'is-amber',
            'icon' => 'bi-diagram-3',
            'message' => 'The case is in a controlled matching or placement-review stage.',
        ],
        'placement', 'supervision' => [
            'class' => 'is-teal',
            'icon' => 'bi-house-heart',
            'message' => 'The case is in placement or post-placement supervision.',
        ],
        default => [
            'class' => 'is-blue',
            'icon' => 'bi-clipboard2-check',
            'message' => 'The case remains active in the adoption review workflow.',
        ],
    };

    $accessPermissions = collect([
        [
            'allowed' => (bool) $access->can_view_summary,
            'label' => 'Case summary',
            'icon' => 'bi-card-text',
        ],
        [
            'allowed' => (bool) $access->can_view_document_status,
            'label' => 'Document status',
            'icon' => 'bi-files',
        ],
        [
            'allowed' => (bool) $access->can_submit_notes,
            'label' => 'Reviewer notes',
            'icon' => 'bi-chat-left-text',
        ],
        [
            'allowed' => (bool) $access->can_make_decision,
            'label' => 'Review decisions',
            'icon' => 'bi-check2-square',
        ],
    ])->filter(fn ($item) => $item['allowed'])->values();

    $latestReviewerNote = $reviewerNotes->first();
@endphp

<style>
    .review-workspace {
        --review-primary: #3157a4;
        --review-primary-dark: #233f7c;
        --review-primary-soft: #eef4ff;
        --review-green: #15803d;
        --review-green-soft: #ecfdf3;
        --review-amber: #b45309;
        --review-amber-soft: #fff7ed;
        --review-red: #b91c1c;
        --review-red-soft: #fff1f2;
        --review-purple: #7c3aed;
        --review-purple-soft: #f5f3ff;
        --review-teal: #0f766e;
        --review-teal-soft: #ecfeff;
        --review-ink: #172033;
        --review-muted: #667085;
        --review-line: #e5e9f0;
        --review-soft: #f7f9fc;
        --review-surface: #ffffff;
        display: grid;
        gap: 18px;
        color: var(--review-ink);
    }

    .review-workspace *,
    .review-workspace *::before,
    .review-workspace *::after {
        box-sizing: border-box;
    }

    .review-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
        padding: 27px;
        border: 1px solid #d8e3f8;
        border-radius: 24px;
        background:
            radial-gradient(circle at 88% 9%, rgba(49, 87, 164, 0.15), transparent 31%),
            linear-gradient(135deg, #f4f8ff 0%, #ffffff 59%, #f8fafc 100%);
        box-shadow: 0 18px 42px rgba(20, 31, 51, 0.07);
    }

    .review-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        padding: 7px 11px;
        border: 1px solid #cbdaf6;
        border-radius: 999px;
        background: var(--review-primary-soft);
        color: var(--review-primary-dark);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .review-hero h1 {
        margin: 0;
        color: #101828;
        font-size: clamp(27px, 3.2vw, 39px);
        line-height: 1.15;
    }

    .review-hero-description {
        max-width: 780px;
        margin: 10px 0 0;
        color: var(--review-muted);
        font-size: 15px;
        line-height: 1.7;
    }

    .review-hero-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .review-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid var(--review-line);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #475467;
        font-size: 12px;
        font-weight: 850;
    }

    .review-hero-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        margin-top: 17px;
    }

    .review-btn {
        min-height: 41px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 13px;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        background: #ffffff;
        color: #344054;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        transition: 0.16s ease;
    }

    .review-btn:hover {
        border-color: #b7bec9;
        background: #f9fafb;
        color: #101828;
    }

    .review-btn.is-primary {
        border-color: var(--review-primary);
        background: var(--review-primary);
        color: #ffffff;
    }

    .review-btn.is-primary:hover {
        border-color: var(--review-primary-dark);
        background: var(--review-primary-dark);
        color: #ffffff;
    }

    .review-btn.is-success {
        border-color: var(--review-green);
        background: var(--review-green);
        color: #ffffff;
    }

    .review-btn.is-success:hover {
        border-color: #166534;
        background: #166534;
        color: #ffffff;
    }

    .review-btn.is-warning {
        border-color: #ea580c;
        background: #ea580c;
        color: #ffffff;
    }

    .review-btn.is-warning:hover {
        border-color: #c2410c;
        background: #c2410c;
        color: #ffffff;
    }

    .review-btn.is-danger {
        border-color: var(--review-red);
        background: var(--review-red);
        color: #ffffff;
    }

    .review-btn.is-danger:hover {
        border-color: #991b1b;
        background: #991b1b;
        color: #ffffff;
    }

    .review-btn:disabled {
        opacity: 0.58;
        cursor: not-allowed;
    }

    .review-btn:focus-visible,
    .review-tab:focus-visible,
    .review-filter:focus-visible,
    .review-search input:focus-visible,
    .review-modal-close:focus-visible,
    .review-disclosure summary:focus-visible {
        outline: 3px solid rgba(49, 87, 164, 0.24);
        outline-offset: 2px;
    }

    .review-status-card {
        width: min(100%, 300px);
        padding: 18px;
        border: 1px solid var(--review-line);
        border-radius: 19px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 14px 30px rgba(20, 31, 51, 0.08);
    }

    .review-status-top {
        display: flex;
        gap: 11px;
        align-items: center;
    }

    .review-status-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 15px;
        background: #dbeafe;
        color: var(--review-primary);
        font-size: 21px;
    }

    .review-status-card.is-amber .review-status-icon {
        background: #fef3c7;
        color: var(--review-amber);
    }

    .review-status-card.is-teal .review-status-icon {
        background: #ccfbf1;
        color: var(--review-teal);
    }

    .review-status-card.is-green .review-status-icon {
        background: #dcfce7;
        color: var(--review-green);
    }

    .review-status-card.is-red .review-status-icon {
        background: #fee2e2;
        color: var(--review-red);
    }

    .review-status-label {
        color: var(--review-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .review-status-value {
        margin-top: 3px;
        color: #101828;
        font-size: 17px;
        font-weight: 950;
        line-height: 1.3;
    }

    .review-status-message {
        margin: 12px 0 0;
        color: var(--review-muted);
        font-size: 12px;
        line-height: 1.55;
    }

    .review-privacy {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        padding: 15px 17px;
        border: 1px solid #fed7aa;
        border-radius: 17px;
        background: #fff8ed;
        color: #9a4f0c;
        line-height: 1.6;
    }

    .review-privacy-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #ffedd5;
        color: #c2410c;
        font-size: 18px;
    }

    .review-privacy strong {
        display: block;
        margin-bottom: 2px;
        color: #8a3e08;
    }

    .review-permissions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .review-permission {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.74);
        color: #9a4f0c;
        font-size: 11px;
        font-weight: 900;
    }

    .review-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .review-stat {
        min-width: 0;
        padding: 17px;
        border: 1px solid var(--review-line);
        border-radius: 18px;
        background: var(--review-surface);
        box-shadow: 0 10px 24px rgba(20, 31, 51, 0.045);
    }

    .review-stat-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
    }

    .review-stat-label {
        color: var(--review-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .review-stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #eff6ff;
        color: var(--review-primary);
        font-size: 18px;
    }

    .review-stat.is-attention .review-stat-icon {
        background: var(--review-red-soft);
        color: var(--review-red);
    }

    .review-stat.is-review .review-stat-icon {
        background: var(--review-purple-soft);
        color: var(--review-purple);
    }

    .review-stat.is-verified .review-stat-icon {
        background: var(--review-green-soft);
        color: var(--review-green);
    }

    .review-stat-value {
        margin-top: 12px;
        color: #101828;
        font-size: 28px;
        font-weight: 950;
        line-height: 1;
    }

    .review-stat-value.is-text {
        font-size: 17px;
        line-height: 1.3;
    }

    .review-stat-help {
        margin-top: 6px;
        color: var(--review-muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .review-attention {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 16px 18px;
        border: 1px solid #fecaca;
        border-radius: 17px;
        background: #fff1f2;
    }

    .review-attention.is-clear {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .review-attention-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #fee2e2;
        color: var(--review-red);
        font-size: 19px;
    }

    .review-attention.is-clear .review-attention-icon {
        background: #dcfce7;
        color: var(--review-green);
    }

    .review-attention-copy strong {
        display: block;
        color: #9f1239;
    }

    .review-attention.is-clear .review-attention-copy strong {
        color: #166534;
    }

    .review-attention-copy p {
        margin: 4px 0 0;
        color: #be123c;
        font-size: 13px;
        line-height: 1.55;
    }

    .review-attention.is-clear .review-attention-copy p {
        color: #166534;
    }

    .review-shell {
        overflow: hidden;
        border: 1px solid var(--review-line);
        border-radius: 22px;
        background: var(--review-surface);
        box-shadow: 0 14px 34px rgba(20, 31, 51, 0.055);
    }

    .review-tabs-wrap {
        padding: 10px;
        border-bottom: 1px solid var(--review-line);
        background: #fbfcfe;
        overflow-x: auto;
    }

    .review-tabs {
        display: flex;
        gap: 7px;
        min-width: max-content;
    }

    .review-tab {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 13px;
        border: 1px solid transparent;
        border-radius: 12px;
        background: transparent;
        color: #667085;
        font-size: 13px;
        font-weight: 900;
        cursor: pointer;
        white-space: nowrap;
    }

    .review-tab:hover {
        background: #f2f4f7;
        color: #344054;
    }

    .review-tab.is-active {
        border-color: #cbdaf6;
        background: var(--review-primary-soft);
        color: var(--review-primary-dark);
    }

    .review-tab-count {
        min-width: 22px;
        min-height: 22px;
        display: inline-grid;
        place-items: center;
        padding: 0 6px;
        border-radius: 999px;
        background: #e4e7ec;
        color: #475467;
        font-size: 10px;
    }

    .review-tab.is-active .review-tab-count {
        background: #d8e4fb;
        color: var(--review-primary-dark);
    }

    .review-panel {
        display: none;
        padding: 22px;
    }

    .review-panel.is-active {
        display: block;
    }

    .review-panel-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .review-panel-header h2 {
        margin: 0;
        color: #101828;
        font-size: 21px;
    }

    .review-panel-header p {
        margin: 6px 0 0;
        color: var(--review-muted);
        line-height: 1.55;
    }

    .review-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border: 1px solid var(--review-line);
        border-radius: 999px;
        background: var(--review-soft);
        color: #475467;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    .review-overview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .review-info-card {
        overflow: hidden;
        border: 1px solid var(--review-line);
        border-radius: 17px;
        background: #ffffff;
    }

    .review-info-card-header {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 15px 16px;
        border-bottom: 1px solid var(--review-line);
        background: #fbfcfe;
    }

    .review-info-card-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .review-info-card-header strong {
        color: #101828;
    }

    .review-info-list {
        display: grid;
    }

    .review-info-row {
        display: grid;
        grid-template-columns: minmax(140px, 0.8fr) minmax(0, 1.2fr);
        gap: 14px;
        padding: 13px 16px;
        border-bottom: 1px solid #f0f2f5;
    }

    .review-info-row:last-child {
        border-bottom: none;
    }

    .review-info-label {
        color: var(--review-muted);
        font-size: 12px;
        font-weight: 800;
    }

    .review-info-value {
        color: #344054;
        font-size: 13px;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .review-summary-box {
        margin-top: 14px;
        padding: 16px;
        border: 1px solid #d8e3f8;
        border-radius: 16px;
        background: #f4f8ff;
    }

    .review-summary-box strong {
        color: var(--review-primary-dark);
    }

    .review-summary-box p {
        margin: 7px 0 0;
        color: #344f88;
        font-size: 13px;
        line-height: 1.65;
        white-space: pre-line;
    }

    .review-doc-progress {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: center;
        margin-bottom: 16px;
        padding: 16px;
        border: 1px solid var(--review-line);
        border-radius: 16px;
        background: var(--review-soft);
    }

    .review-progress-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 9px;
    }

    .review-progress-top span {
        color: #475467;
        font-size: 12px;
        font-weight: 850;
    }

    .review-progress-top strong {
        color: #101828;
        font-size: 20px;
    }

    .review-progress-track {
        height: 10px;
        overflow: hidden;
        border-radius: 999px;
        background: #e4e7ec;
    }

    .review-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--review-primary), #22c55e);
    }

    .review-progress-number {
        min-width: 94px;
        text-align: right;
    }

    .review-progress-number strong {
        display: block;
        color: #101828;
        font-size: 29px;
    }

    .review-progress-number span {
        color: var(--review-muted);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .review-doc-toolbar {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 12px;
        align-items: center;
        margin-bottom: 14px;
    }

    .review-search {
        position: relative;
    }

    .review-search > i {
        position: absolute;
        top: 50%;
        left: 14px;
        color: #98a2b3;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .review-search input {
        width: 100%;
        min-height: 44px;
        padding: 0 42px 0 40px;
        border: 1px solid #d0d5dd;
        border-radius: 13px;
        background: #ffffff;
        color: #101828;
        font: inherit;
        outline: none;
        transition: 0.18s ease;
    }

    .review-search input:focus {
        border-color: var(--review-primary);
        box-shadow: 0 0 0 4px rgba(49, 87, 164, 0.11);
    }

    .review-search-clear {
        position: absolute;
        top: 50%;
        right: 8px;
        width: 31px;
        height: 31px;
        display: none;
        place-items: center;
        border: none;
        border-radius: 9px;
        background: transparent;
        color: #667085;
        cursor: pointer;
        transform: translateY(-50%);
    }

    .review-search-clear.is-visible {
        display: grid;
    }

    .review-search-clear:hover {
        background: #f2f4f7;
    }

    .review-filter-row {
        display: flex;
        gap: 7px;
        align-items: center;
        flex-wrap: wrap;
    }

    .review-filter {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        background: #ffffff;
        color: #475467;
        font-size: 12px;
        font-weight: 850;
        cursor: pointer;
        transition: 0.16s ease;
    }

    .review-filter:hover {
        border-color: #b7bec9;
        background: #f9fafb;
    }

    .review-filter.is-active {
        border-color: var(--review-primary);
        background: var(--review-primary-soft);
        color: var(--review-primary-dark);
        box-shadow: 0 0 0 3px rgba(49, 87, 164, 0.08);
    }

    .review-doc-list {
        display: grid;
        gap: 10px;
    }

    .review-document {
        border: 1px solid var(--review-line);
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
    }

    .review-document[hidden] {
        display: none !important;
    }

    .review-document-main {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 13px;
        align-items: center;
        padding: 15px;
    }

    .review-document-icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 14px;
        background: #f2f4f7;
        color: #667085;
        font-size: 19px;
    }

    .review-document.is-pending .review-document-icon {
        background: #fef3c7;
        color: #92400e;
    }

    .review-document.is-submitted .review-document-icon,
    .review-document.is-review .review-document-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .review-document.is-verified .review-document-icon {
        background: #dcfce7;
        color: #166534;
    }

    .review-document.is-rejected .review-document-icon,
    .review-document.is-expired .review-document-icon {
        background: #fee2e2;
        color: #991b1b;
    }

    .review-document-copy {
        min-width: 0;
    }

    .review-document-title-row {
        display: flex;
        gap: 7px;
        align-items: center;
        flex-wrap: wrap;
    }

    .review-document-title {
        margin: 0;
        color: #344054;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.4;
    }

    .review-status,
    .review-scope {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 900;
        white-space: nowrap;
    }

    .review-status {
        background: #f2f4f7;
        color: #667085;
    }

    .review-status.is-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .review-status.is-submitted {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .review-status.is-review {
        background: #ede9fe;
        color: #6d28d9;
    }

    .review-status.is-verified {
        background: #dcfce7;
        color: #166534;
    }

    .review-status.is-rejected,
    .review-status.is-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    .review-scope.is-parent {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .review-scope.is-child {
        background: #ecfdf3;
        color: #166534;
    }

    .review-scope.is-case {
        background: #f5f3ff;
        color: #6d28d9;
    }

    .review-document-meta {
        display: flex;
        gap: 7px 13px;
        flex-wrap: wrap;
        margin-top: 6px;
        color: var(--review-muted);
        font-size: 11px;
    }

    .review-document-meta span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
    }

    .review-document-filename {
        max-width: 390px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .review-document-actions {
        display: flex;
        gap: 7px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .review-document-details {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        padding: 0 15px 15px 72px;
    }

    .review-document-details[hidden] {
        display: none;
    }

    .review-detail-box {
        min-width: 0;
        padding: 12px;
        border: 1px solid var(--review-line);
        border-radius: 13px;
        background: var(--review-soft);
    }

    .review-detail-box.is-wide {
        grid-column: span 3;
    }

    .review-detail-label {
        display: block;
        margin-bottom: 5px;
        color: #667085;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .review-detail-value {
        color: #344054;
        font-size: 12px;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .review-no-results {
        display: none;
        padding: 40px 20px;
        text-align: center;
        color: var(--review-muted);
    }

    .review-no-results.is-visible {
        display: block;
    }

    .review-no-results-icon,
    .review-empty-icon {
        width: 59px;
        height: 59px;
        display: grid;
        place-items: center;
        margin: 0 auto 12px;
        border-radius: 19px;
        background: #f2f4f7;
        color: #667085;
        font-size: 25px;
    }

    .review-no-results h3,
    .review-empty h3 {
        margin: 0;
        color: #101828;
    }

    .review-no-results p,
    .review-empty p {
        max-width: 620px;
        margin: 7px auto 0;
        line-height: 1.65;
    }

    .review-decision-overview {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .review-decision-card {
        display: grid;
        gap: 12px;
        padding: 17px;
        border: 1px solid var(--review-line);
        border-radius: 17px;
        background: #ffffff;
    }

    .review-decision-card.is-approve {
        border-color: #bbf7d0;
        background: #f7fef9;
    }

    .review-decision-card.is-changes {
        border-color: #fed7aa;
        background: #fffaf5;
    }

    .review-decision-card-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #dcfce7;
        color: var(--review-green);
        font-size: 19px;
    }

    .review-decision-card.is-changes .review-decision-card-icon {
        background: #ffedd5;
        color: #c2410c;
    }

    .review-decision-card h3 {
        margin: 0;
        color: #101828;
        font-size: 16px;
    }

    .review-decision-card p {
        margin: 5px 0 0;
        color: var(--review-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .review-decision-card .review-btn {
        justify-self: start;
    }

    .review-note-composer {
        margin-bottom: 17px;
        border: 1px solid var(--review-line);
        border-radius: 17px;
        background: #ffffff;
        overflow: hidden;
    }

    .review-disclosure summary {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 15px 16px;
        list-style: none;
        cursor: pointer;
        user-select: none;
    }

    .review-disclosure summary::-webkit-details-marker {
        display: none;
    }

    .review-disclosure-title {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .review-disclosure-title i {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: var(--review-primary-soft);
        color: var(--review-primary);
    }

    .review-disclosure-title strong {
        display: block;
        color: #101828;
        font-size: 14px;
    }

    .review-disclosure-title span {
        display: block;
        margin-top: 2px;
        color: var(--review-muted);
        font-size: 11px;
    }

    .review-disclosure-chevron {
        color: #667085;
        transition: transform 0.2s ease;
    }

    .review-disclosure[open] .review-disclosure-chevron {
        transform: rotate(180deg);
    }

    .review-note-form {
        display: grid;
        gap: 13px;
        padding: 16px;
        border-top: 1px solid var(--review-line);
    }

    .review-field label {
        display: block;
        margin-bottom: 7px;
        color: #344054;
        font-size: 12px;
        font-weight: 900;
    }

    .review-field input,
    .review-field textarea {
        width: 100%;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        padding: 11px 12px;
        background: #ffffff;
        color: #101828;
        font: inherit;
        outline: none;
        transition: 0.16s ease;
    }

    .review-field textarea {
        min-height: 120px;
        resize: vertical;
    }

    .review-field input:focus,
    .review-field textarea:focus {
        border-color: var(--review-primary);
        box-shadow: 0 0 0 4px rgba(49, 87, 164, 0.11);
    }

    .review-field-help {
        margin-top: 5px;
        color: var(--review-muted);
        font-size: 11px;
        line-height: 1.45;
    }

    .review-character-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 5px;
        color: var(--review-muted);
        font-size: 11px;
    }

    .review-note-list {
        display: grid;
        gap: 10px;
    }

    .review-note {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--review-line);
        border-radius: 15px;
        background: #ffffff;
    }

    .review-note-icon {
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .review-note-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .review-note-header strong {
        color: #344054;
    }

    .review-note-meta {
        color: var(--review-muted);
        font-size: 11px;
        font-weight: 800;
    }

    .review-note-body {
        margin-top: 7px;
        color: #475467;
        font-size: 13px;
        line-height: 1.65;
        white-space: pre-line;
    }

    .review-empty {
        padding: 34px 20px;
        text-align: center;
        color: var(--review-muted);
    }

    .review-modal[hidden] {
        display: none !important;
    }

    .review-modal {
        --review-primary: #3157a4;
        --review-primary-dark: #233f7c;
        --review-primary-soft: #eef4ff;
        --review-green: #15803d;
        --review-red: #b91c1c;
        --review-muted: #667085;
        --review-line: #e5e9f0;
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: grid;
        place-items: center;
        padding: 20px;
    }

    .review-modal-backdrop {
        position: absolute;
        inset: 0;
        border: none;
        background: rgba(15, 23, 42, 0.64);
        backdrop-filter: blur(5px);
        cursor: default;
    }

    .review-modal-dialog {
        position: relative;
        z-index: 1;
        width: min(100%, 610px);
        max-height: min(88vh, 760px);
        overflow-y: auto;
        border: 1px solid #e4e7ec;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
    }

    .review-modal-header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding: 20px 21px 16px;
        border-bottom: 1px solid var(--review-line);
    }

    .review-modal-icon {
        width: 45px;
        height: 45px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 14px;
        background: var(--review-primary-soft);
        color: var(--review-primary);
        font-size: 20px;
    }

    .review-modal-heading {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 11px;
        align-items: center;
    }

    .review-modal-header h2 {
        margin: 0;
        color: #101828;
        font-size: 20px;
    }

    .review-modal-header p {
        margin: 5px 0 0;
        color: var(--review-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .review-modal-close {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border: 1px solid #d0d5dd;
        border-radius: 11px;
        background: #ffffff;
        color: #475467;
        cursor: pointer;
    }

    .review-modal-close:hover {
        background: #f9fafb;
    }

    .review-modal-body {
        display: grid;
        gap: 14px;
        padding: 20px 21px;
    }

    .review-modal-notice {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 12px;
        line-height: 1.55;
    }

    .review-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 16px 21px 20px;
        border-top: 1px solid var(--review-line);
    }

    body.review-modal-open {
        overflow: hidden;
    }

    @media (max-width: 1120px) {
        .review-stats,
        .review-overview-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .review-doc-toolbar {
            grid-template-columns: 1fr;
        }

        .review-document-main {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .review-document-actions {
            grid-column: 2;
            justify-content: flex-start;
        }

        .review-document-details {
            padding-left: 72px;
        }
    }

    @media (max-width: 760px) {
        .review-workspace {
            gap: 14px;
        }

        .review-hero {
            grid-template-columns: 1fr;
            padding: 21px;
        }

        .review-status-card {
            width: 100%;
        }

        .review-stats,
        .review-overview-grid,
        .review-decision-overview {
            grid-template-columns: 1fr;
        }

        .review-attention {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .review-attention .review-btn {
            grid-column: 1 / -1;
        }

        .review-panel {
            padding: 17px;
        }

        .review-info-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .review-filter-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .review-filter {
            width: 100%;
        }

        .review-document-main {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .review-document-actions {
            grid-column: 1 / -1;
            justify-content: stretch;
        }

        .review-document-actions .review-btn {
            flex: 1 1 120px;
        }

        .review-document-details {
            grid-template-columns: 1fr;
            padding: 0 15px 15px;
        }

        .review-detail-box.is-wide {
            grid-column: auto;
        }

        .review-doc-progress {
            grid-template-columns: 1fr;
        }

        .review-progress-number {
            text-align: left;
        }

        .review-modal {
            align-items: end;
            padding: 0;
        }

        .review-modal-dialog {
            width: 100%;
            max-height: 92vh;
            border-radius: 22px 22px 0 0;
        }
    }

    @media (max-width: 480px) {
        .review-hero-actions,
        .review-hero-actions .review-btn {
            width: 100%;
        }

        .review-filter-row {
            grid-template-columns: 1fr;
        }

        .review-note {
            grid-template-columns: 1fr;
        }

        .review-modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .review-workspace *,
        .review-workspace *::before,
        .review-workspace *::after {
            scroll-behavior: auto !important;
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<div class="review-workspace">
    <section class="review-hero">
        <div>
            <div class="review-eyebrow">
                <i class="bi bi-shield-check"></i>
                Authorized reviewer workspace
            </div>

            <h1>Case {{ $adoptionCase->case_code }}</h1>

            <p class="review-hero-description">
                Review the authorized case summary, evaluate document readiness, record professional
                observations, and submit only the decisions permitted by your current access.
            </p>

            <div class="review-hero-meta">
                <span class="review-meta-pill">
                    <i class="bi bi-briefcase"></i>
                    {{ $adoptionCase->case_type_label }}
                </span>

                <span class="review-meta-pill">
                    <i class="bi bi-flag"></i>
                    {{ $adoptionCase->priority_label }} priority
                </span>

                <span class="review-meta-pill">
                    <i class="bi bi-person-check"></i>
                    Authorized by {{ $access->authorizer?->name ?? 'N/A' }}
                </span>

                <span class="review-meta-pill">
                    <i class="bi bi-calendar-event"></i>
                    Access expires {{ $access->expires_at?->format('M d, Y') ?? 'without a set date' }}
                </span>
            </div>

            <div class="review-hero-actions">
                <a href="{{ route('reviewer.cases.index') }}" class="review-btn">
                    <i class="bi bi-arrow-left"></i>
                    Authorized cases
                </a>

                <a href="{{ route('reviewer.dashboard') }}" class="review-btn">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>

                @if($access->can_view_document_status)
                    <button
                        type="button"
                        class="review-btn is-primary"
                        data-review-tab-open="documents"
                    >
                        <i class="bi bi-files"></i>
                        Review documents
                    </button>
                @endif
            </div>
        </div>

        <aside class="review-status-card {{ $caseStatusMeta['class'] }}">
            <div class="review-status-top">
                <div class="review-status-icon">
                    <i class="bi {{ $caseStatusMeta['icon'] }}"></i>
                </div>

                <div>
                    <div class="review-status-label">Current case status</div>
                    <div class="review-status-value">
                        {{ $adoptionCase->status_label }}
                    </div>
                </div>
            </div>

            <p class="review-status-message">
                {{ $caseStatusMeta['message'] }}
            </p>
        </aside>
    </section>

    <section class="review-privacy" aria-label="Reviewer privacy reminder">
        <div class="review-privacy-icon">
            <i class="bi bi-shield-lock"></i>
        </div>

        <div>
            <strong>Use only the information and actions authorized for this review.</strong>
            Full child records, donor information, confidential internal notes, matching rankings,
            and final placement decisions remain restricted unless separately authorized.

            @if($accessPermissions->count())
                <div class="review-permissions" aria-label="Current access permissions">
                    @foreach($accessPermissions as $permission)
                        <span class="review-permission">
                            <i class="bi {{ $permission['icon'] }}"></i>
                            {{ $permission['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="review-stats" aria-label="Case review overview">
        <article class="review-stat">
            <div class="review-stat-top">
                <div class="review-stat-label">Documents</div>
                <div class="review-stat-icon">
                    <i class="bi bi-files"></i>
                </div>
            </div>

            <div class="review-stat-value">{{ $documentSummary['total'] }}</div>
            <div class="review-stat-help">All requirements in the authorized checklist</div>
        </article>

        <article class="review-stat is-attention">
            <div class="review-stat-top">
                <div class="review-stat-label">Need attention</div>
                <div class="review-stat-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
            </div>

            <div class="review-stat-value">{{ $attentionDocuments->count() }}</div>
            <div class="review-stat-help">Pending, rejected, or expired requirements</div>
        </article>

        <article class="review-stat is-review">
            <div class="review-stat-top">
                <div class="review-stat-label">Ready for review</div>
                <div class="review-stat-icon">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <div class="review-stat-value">{{ $submittedForReviewCount }}</div>
            <div class="review-stat-help">Submitted or under-review files</div>
        </article>

        <article class="review-stat is-verified">
            <div class="review-stat-top">
                <div class="review-stat-label">Verified</div>
                <div class="review-stat-icon">
                    <i class="bi bi-patch-check"></i>
                </div>
            </div>

            <div class="review-stat-value">{{ $safeProgress }}%</div>
            <div class="review-stat-help">
                {{ $documentSummary['verified'] }} of {{ $documentSummary['total'] }} requirements accepted
            </div>
        </article>
    </section>

    @if($access->can_view_document_status && $attentionDocuments->count() > 0)
        <section class="review-attention">
            <div class="review-attention-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <div class="review-attention-copy">
                <strong>
                    {{ $attentionDocuments->count() }}
                    document {{ $attentionDocuments->count() === 1 ? 'requires' : 'require' }}
                    follow-up
                </strong>

                <p>
                    Review pending, rejected, and expired requirements before submitting a case-level decision.
                </p>
            </div>

            <button
                type="button"
                class="review-btn is-danger"
                data-review-tab-open="documents"
                data-document-filter-target="attention"
            >
                Review attention items
                <i class="bi bi-arrow-right"></i>
            </button>
        </section>
    @elseif($access->can_view_document_status)
        <section class="review-attention is-clear">
            <div class="review-attention-icon">
                <i class="bi bi-check2-circle"></i>
            </div>

            <div class="review-attention-copy">
                <strong>No pending, rejected, or expired requirements</strong>
                <p>
                    Continue reviewing submitted files and the authorized case summary before recording a decision.
                </p>
            </div>

            <button
                type="button"
                class="review-btn"
                data-review-tab-open="documents"
            >
                View checklist
                <i class="bi bi-arrow-right"></i>
            </button>
        </section>
    @endif

    <section class="review-shell">
        <div class="review-tabs-wrap">
            <div class="review-tabs" role="tablist" aria-label="Authorized case sections">
                <button
                    type="button"
                    class="review-tab is-active"
                    id="reviewTabOverview"
                    role="tab"
                    aria-selected="true"
                    aria-controls="reviewPanelOverview"
                    data-review-tab="overview"
                >
                    <i class="bi bi-grid"></i>
                    Overview
                </button>

                @if($access->can_view_document_status)
                    <button
                        type="button"
                        class="review-tab"
                        id="reviewTabDocuments"
                        role="tab"
                        aria-selected="false"
                        aria-controls="reviewPanelDocuments"
                        data-review-tab="documents"
                    >
                        <i class="bi bi-files"></i>
                        Documents
                        <span class="review-tab-count">{{ $documents->count() }}</span>
                    </button>
                @endif

                @if($access->can_make_decision)
                    <button
                        type="button"
                        class="review-tab"
                        id="reviewTabDecision"
                        role="tab"
                        aria-selected="false"
                        aria-controls="reviewPanelDecision"
                        data-review-tab="decision"
                    >
                        <i class="bi bi-check2-square"></i>
                        Decision
                    </button>
                @endif

                <button
                    type="button"
                    class="review-tab"
                    id="reviewTabNotes"
                    role="tab"
                    aria-selected="false"
                    aria-controls="reviewPanelNotes"
                    data-review-tab="notes"
                >
                    <i class="bi bi-chat-left-text"></i>
                    Notes
                    <span class="review-tab-count">{{ $reviewerNotes->count() }}</span>
                </button>
            </div>
        </div>

        <div
            class="review-panel is-active"
            id="reviewPanelOverview"
            role="tabpanel"
            aria-labelledby="reviewTabOverview"
            data-review-panel="overview"
        >
            <div class="review-panel-header">
                <div>
                    <h2>Authorized case overview</h2>
                    <p>Key case, applicant, assignment, and access information.</p>
                </div>

                <span class="review-badge">
                    <i class="bi bi-shield-check"></i>
                    Reviewer access active
                </span>
            </div>

            @if($access->can_view_summary)
                <div class="review-overview-grid">
                    <article class="review-info-card">
                        <header class="review-info-card-header">
                            <div class="review-info-card-icon">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <strong>Case information</strong>
                        </header>

                        <div class="review-info-list">
                            <div class="review-info-row">
                                <span class="review-info-label">Case code</span>
                                <span class="review-info-value">{{ $adoptionCase->case_code }}</span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Case type</span>
                                <span class="review-info-value">{{ $adoptionCase->case_type_label }}</span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Case status</span>
                                <span class="review-info-value">{{ $adoptionCase->status_label }}</span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">RACCO review</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->racco_review_status_label ?? 'Not submitted' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Priority</span>
                                <span class="review-info-value">{{ $adoptionCase->priority_label }}</span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Opened date</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->opened_at?->format('M d, Y') ?? 'Not set' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Target completion</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->target_completion_date?->format('M d, Y') ?? 'Not set' }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <article class="review-info-card">
                        <header class="review-info-card-header">
                            <div class="review-info-card-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <strong>Authorized participants</strong>
                        </header>

                        <div class="review-info-list">
                            <div class="review-info-row">
                                <span class="review-info-label">Child reference</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->child?->child_code ?? 'Restricted' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Prospective parent</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->prospectiveParent?->name ?? 'Not assigned' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Assigned staff</span>
                                <span class="review-info-value">
                                    {{ $adoptionCase->assignedSocialWorker?->name ?? 'Not assigned' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Access authorized by</span>
                                <span class="review-info-value">
                                    {{ $access->authorizer?->name ?? 'N/A' }}
                                </span>
                            </div>

                            <div class="review-info-row">
                                <span class="review-info-label">Access expiry</span>
                                <span class="review-info-value">
                                    {{ $access->expires_at?->format('M d, Y h:i A') ?? 'No expiry set' }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <article class="review-summary-box">
                    <strong>
                        <i class="bi bi-card-text"></i>
                        Case summary
                    </strong>

                    <p>
                        {{ $adoptionCase->summary ?? 'No case summary has been provided for external review.' }}
                    </p>
                </article>

                @if($latestReviewerNote)
                    <article class="review-summary-box">
                        <strong>
                            <i class="bi bi-chat-square-text"></i>
                            Latest reviewer note
                        </strong>

                        <p>
                            {{ $latestReviewerNote->title ?? 'Reviewer Note' }}:
                            {{ $latestReviewerNote->body }}
                        </p>
                    </article>
                @endif
            @else
                <div class="review-empty">
                    <div class="review-empty-icon">
                        <i class="bi bi-lock"></i>
                    </div>

                    <h3>Case summary restricted</h3>
                    <p>Your reviewer access does not currently include the full case summary.</p>
                </div>
            @endif
        </div>

        @if($access->can_view_document_status)
            <div
                class="review-panel"
                id="reviewPanelDocuments"
                role="tabpanel"
                aria-labelledby="reviewTabDocuments"
                data-review-panel="documents"
                hidden
            >
                <div class="review-panel-header">
                    <div>
                        <h2>Document review checklist</h2>
                        <p>
                            Search, filter, inspect, download, and—when permitted—accept or return submitted files.
                        </p>
                    </div>

                    <span class="review-badge" aria-live="polite">
                        <i class="bi bi-list-check"></i>
                        <span id="reviewVisibleDocumentCount">{{ $documents->count() }}</span>
                        of {{ $documents->count() }} shown
                    </span>
                </div>

                <div class="review-doc-progress">
                    <div>
                        <div class="review-progress-top">
                            <span>Overall verification progress</span>
                            <strong>
                                {{ $documentSummary['verified'] }} / {{ $documentSummary['total'] }}
                            </strong>
                        </div>

                        <div
                            class="review-progress-track"
                            role="progressbar"
                            aria-valuenow="{{ $safeProgress }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Document verification progress"
                        >
                            <div
                                class="review-progress-fill"
                                style="width: {{ $safeProgress }}%;"
                            ></div>
                        </div>
                    </div>

                    <div class="review-progress-number">
                        <strong>{{ $safeProgress }}%</strong>
                        <span>Verified</span>
                    </div>
                </div>

                <div class="review-doc-toolbar">
                    <div class="review-search">
                        <i class="bi bi-search"></i>

                        <label for="reviewDocumentSearch" class="visually-hidden">
                            Search case documents
                        </label>

                        <input
                            type="search"
                            id="reviewDocumentSearch"
                            placeholder="Search document name, type, status, filename, or remarks..."
                            autocomplete="off"
                        >

                        <button
                            type="button"
                            class="review-search-clear"
                            id="reviewDocumentSearchClear"
                            aria-label="Clear document search"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="review-filter-row" role="group" aria-label="Filter case documents">
                        <button type="button" class="review-filter is-active" data-document-filter="all">
                            <i class="bi bi-grid"></i>
                            All
                        </button>

                        <button type="button" class="review-filter" data-document-filter="attention">
                            <i class="bi bi-exclamation-circle"></i>
                            Attention
                        </button>

                        <button type="button" class="review-filter" data-document-filter="review">
                            <i class="bi bi-search"></i>
                            Ready for review
                        </button>

                        <button type="button" class="review-filter" data-document-filter="verified">
                            <i class="bi bi-patch-check"></i>
                            Verified
                        </button>

                        <button type="button" class="review-filter" data-document-filter="parent">
                            Parent
                        </button>

                        <button type="button" class="review-filter" data-document-filter="child">
                            Child legal
                        </button>

                        <button type="button" class="review-filter" data-document-filter="case">
                            Case
                        </button>
                    </div>
                </div>

                @if($documents->count())
                    <div class="review-doc-list" id="reviewDocumentList">
                        @foreach($documents as $document)
                            @php
                                $status = $statusMeta[$document->status] ?? [
                                    'label' => $document->status_label ?? ucfirst((string) $document->status),
                                    'class' => 'is-pending',
                                    'icon' => 'bi-info-circle',
                                    'description' => 'Review this requirement with authorized staff.',
                                ];

                                $scope = $scopeMeta[$document->requirement_scope] ?? [
                                    'label' => $document->scope_label ?? ucfirst((string) $document->requirement_scope),
                                    'class' => 'is-case',
                                    'icon' => 'bi-folder',
                                ];

                                $isAttention = in_array(
                                    $document->status,
                                    ['pending', 'rejected', 'expired'],
                                    true
                                );

                                $isReadyForReview = in_array(
                                    $document->status,
                                    ['submitted', 'under_review'],
                                    true
                                );

                                $searchText = strtolower(implode(' ', array_filter([
                                    $document->document_name,
                                    $document->document_type,
                                    $document->status,
                                    $document->status_label ?? null,
                                    $document->requirement_scope,
                                    $document->scope_label ?? null,
                                    $document->original_filename,
                                    $document->remarks,
                                ])));
                            @endphp

                            <article
                                class="review-document {{ $status['class'] }}"
                                data-review-document
                                data-search="{{ $searchText }}"
                                data-status="{{ $document->status }}"
                                data-scope="{{ $document->requirement_scope }}"
                                data-attention="{{ $isAttention ? '1' : '0' }}"
                                data-ready-review="{{ $isReadyForReview ? '1' : '0' }}"
                            >
                                <div class="review-document-main">
                                    <div class="review-document-icon" aria-hidden="true">
                                        <i class="bi {{ $status['icon'] }}"></i>
                                    </div>

                                    <div class="review-document-copy">
                                        <div class="review-document-title-row">
                                            <h3 class="review-document-title">
                                                {{ $document->document_name }}
                                            </h3>

                                            <span class="review-status {{ $status['class'] }}">
                                                <i class="bi {{ $status['icon'] }}"></i>
                                                {{ $status['label'] }}
                                            </span>

                                            <span class="review-scope {{ $scope['class'] }}">
                                                <i class="bi {{ $scope['icon'] }}"></i>
                                                {{ $scope['label'] }}
                                            </span>
                                        </div>

                                        <div class="review-document-meta">
                                            <span>
                                                <i class="bi bi-tag"></i>
                                                {{ $document->document_type ?? 'No document type' }}
                                            </span>

                                            @if($document->original_filename)
                                                <span
                                                    class="review-document-filename"
                                                    title="{{ $document->original_filename }}"
                                                >
                                                    <i class="bi bi-paperclip"></i>
                                                    {{ $document->original_filename }}
                                                </span>
                                            @elseif($document->file_path)
                                                <span>
                                                    <i class="bi bi-file-earmark-check"></i>
                                                    Submitted file available
                                                </span>
                                            @else
                                                <span>
                                                    <i class="bi bi-cloud-slash"></i>
                                                    No file submitted
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="review-document-actions">
                                        <button
                                            type="button"
                                            class="review-btn"
                                            data-review-details-toggle="{{ $document->id }}"
                                            aria-expanded="false"
                                            aria-controls="reviewDocumentDetails{{ $document->id }}"
                                        >
                                            <i class="bi bi-info-circle"></i>
                                            Details
                                        </button>

                                        @if($document->file_path)
                                            <a
                                                href="{{ route('reviewer.cases.documents.download', [$access, $document]) }}"
                                                class="review-btn"
                                            >
                                                <i class="bi bi-download"></i>
                                                Download
                                            </a>
                                        @endif

                                        @if($access->can_make_decision && $document->file_path)
                                            @if($document->status !== 'verified')
                                                <button
                                                    type="button"
                                                    class="review-btn is-success"
                                                    data-decision-open
                                                    data-decision-action="{{ route('reviewer.cases.documents.accept', [$access, $document]) }}"
                                                    data-decision-mode="accept-document"
                                                    data-decision-title="Accept submitted document"
                                                    data-decision-subtitle="{{ $document->document_name }}"
                                                    data-decision-message="Confirm that the submitted file is acceptable for this requirement. Optional remarks will be recorded in the case history."
                                                    data-decision-required="0"
                                                >
                                                    <i class="bi bi-check-circle"></i>
                                                    Accept
                                                </button>
                                            @endif

                                            @if($document->status !== 'rejected')
                                                <button
                                                    type="button"
                                                    class="review-btn is-danger"
                                                    data-decision-open
                                                    data-decision-action="{{ route('reviewer.cases.documents.reject', [$access, $document]) }}"
                                                    data-decision-mode="reject-document"
                                                    data-decision-title="Return document for revision"
                                                    data-decision-subtitle="{{ $document->document_name }}"
                                                    data-decision-message="Explain clearly why the file cannot be accepted and what the applicant or staff must correct."
                                                    data-decision-required="1"
                                                >
                                                    <i class="bi bi-x-circle"></i>
                                                    Reject
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="review-document-details"
                                    id="reviewDocumentDetails{{ $document->id }}"
                                    hidden
                                >
                                    <div class="review-detail-box">
                                        <span class="review-detail-label">Status guidance</span>
                                        <div class="review-detail-value">
                                            {{ $status['description'] }}
                                        </div>
                                    </div>

                                    <div class="review-detail-box">
                                        <span class="review-detail-label">Expiry date</span>
                                        <div class="review-detail-value">
                                            {{ $document->expiry_date?->format('M d, Y') ?? 'No expiry date recorded' }}
                                        </div>
                                    </div>

                                    <div class="review-detail-box">
                                        <span class="review-detail-label">Verified date</span>
                                        <div class="review-detail-value">
                                            {{ $document->verified_at?->format('M d, Y h:i A') ?? 'Not yet verified' }}
                                        </div>
                                    </div>

                                    <div class="review-detail-box is-wide">
                                        <span class="review-detail-label">Remarks</span>
                                        <div class="review-detail-value">
                                            {{ $document->remarks ?: 'No remarks have been recorded for this requirement.' }}
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="review-no-results" id="reviewDocumentNoResults">
                        <div class="review-no-results-icon">
                            <i class="bi bi-search"></i>
                        </div>

                        <h3>No matching documents</h3>
                        <p>Try another search term or choose a different filter.</p>
                    </div>
                @else
                    <div class="review-empty">
                        <div class="review-empty-icon">
                            <i class="bi bi-file-earmark"></i>
                        </div>

                        <h3>No documents in this case</h3>
                        <p>The authorized case does not currently contain document requirements.</p>
                    </div>
                @endif
            </div>
        @endif

        @if($access->can_make_decision)
            <div
                class="review-panel"
                id="reviewPanelDecision"
                role="tabpanel"
                aria-labelledby="reviewTabDecision"
                data-review-panel="decision"
                hidden
            >
                <div class="review-panel-header">
                    <div>
                        <h2>RACCO review decision</h2>
                        <p>
                            Record a case-level decision only after reviewing the authorized case summary,
                            document checklist, and reviewer notes.
                        </p>
                    </div>

                    <span class="review-badge">
                        <i class="bi bi-shield-check"></i>
                        Decision permission enabled
                    </span>
                </div>

                @if($attentionDocuments->count())
                    <div class="review-attention" style="margin-bottom: 16px;">
                        <div class="review-attention-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                        <div class="review-attention-copy">
                            <strong>Outstanding document concerns are present</strong>
                            <p>
                                {{ $attentionDocuments->count() }}
                                requirement {{ $attentionDocuments->count() === 1 ? 'is' : 'are' }}
                                pending, rejected, or expired. Confirm that your case-level decision accounts for these items.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="review-btn"
                            data-review-tab-open="documents"
                            data-document-filter-target="attention"
                        >
                            Review items
                        </button>
                    </div>
                @endif

                <div class="review-decision-overview">
                    <article class="review-decision-card is-approve">
                        <div class="review-decision-card-icon">
                            <i class="bi bi-check2-circle"></i>
                        </div>

                        <div>
                            <h3>Approve case review</h3>
                            <p>
                                Use this only when the authorized information is acceptable for the next
                                workflow stage. Approval does not independently finalize an adoption.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="review-btn is-success"
                            data-decision-open
                            data-decision-action="{{ route('reviewer.cases.approve', $access) }}"
                            data-decision-mode="approve-case"
                            data-decision-title="Approve case review"
                            data-decision-subtitle="Case {{ $adoptionCase->case_code }}"
                            data-decision-message="Your approval will be recorded as the RACCO review decision and will move the case to the configured next stage."
                            data-decision-required="0"
                        >
                            <i class="bi bi-check-circle"></i>
                            Continue to approval
                        </button>
                    </article>

                    <article class="review-decision-card is-changes">
                        <div class="review-decision-card-icon">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </div>

                        <div>
                            <h3>Request changes</h3>
                            <p>
                                Use this when documents, case details, or staff follow-up must be completed
                                before the case can proceed.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="review-btn is-warning"
                            data-decision-open
                            data-decision-action="{{ route('reviewer.cases.request-changes', $access) }}"
                            data-decision-mode="request-changes"
                            data-decision-title="Request case changes"
                            data-decision-subtitle="Case {{ $adoptionCase->case_code }}"
                            data-decision-message="Describe the required corrections or follow-up clearly. These remarks will guide staff and may be reflected in parent-visible updates."
                            data-decision-required="1"
                        >
                            <i class="bi bi-exclamation-circle"></i>
                            Continue to request changes
                        </button>
                    </article>
                </div>
            </div>
        @endif

        <div
            class="review-panel"
            id="reviewPanelNotes"
            role="tabpanel"
            aria-labelledby="reviewTabNotes"
            data-review-panel="notes"
            hidden
        >
            <div class="review-panel-header">
                <div>
                    <h2>Reviewer notes</h2>
                    <p>
                        Add professional observations and review the authorized reviewer-summary history.
                    </p>
                </div>

                <span class="review-badge">
                    <i class="bi bi-chat-left-text"></i>
                    {{ $reviewerNotes->count() }}
                    {{ $reviewerNotes->count() === 1 ? 'note' : 'notes' }}
                </span>
            </div>

            @if($access->can_submit_notes)
                <details class="review-note-composer review-disclosure" {{ old('body') ? 'open' : '' }}>
                    <summary>
                        <div class="review-disclosure-title">
                            <i class="bi bi-pencil-square"></i>

                            <div>
                                <strong>Add a reviewer note</strong>
                                <span>Record observations, document feedback, or follow-up context</span>
                            </div>
                        </div>

                        <i class="bi bi-chevron-down review-disclosure-chevron"></i>
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('reviewer.cases.notes.store', $access) }}"
                        class="review-note-form"
                    >
                        @csrf

                        <div class="review-field">
                            <label for="review_note_title">Note title</label>
                            <input
                                type="text"
                                id="review_note_title"
                                name="title"
                                maxlength="150"
                                placeholder="Example: Document review feedback"
                                value="{{ old('title') }}"
                            >
                            <div class="review-field-help">
                                Use a short title that makes the note easy to find later.
                            </div>
                        </div>

                        <div class="review-field">
                            <label for="review_note_body">Review note</label>
                            <textarea
                                id="review_note_body"
                                name="body"
                                maxlength="5000"
                                placeholder="Write a clear, factual, and professional review note..."
                                required
                            >{{ old('body') }}</textarea>

                            <div class="review-character-row">
                                <span>Maximum 5,000 characters</span>
                                <span id="reviewNoteCharacterCount">0 / 5000</span>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="review-btn is-primary">
                                <i class="bi bi-send"></i>
                                Submit reviewer note
                            </button>
                        </div>
                    </form>
                </details>
            @else
                <div class="review-privacy" style="margin-bottom: 16px;">
                    <div class="review-privacy-icon">
                        <i class="bi bi-lock"></i>
                    </div>

                    <div>
                        <strong>Note submission is not enabled.</strong>
                        Your access allows you to view reviewer-summary notes but not create new ones.
                    </div>
                </div>
            @endif

            @if($reviewerNotes->count())
                <div class="review-note-list">
                    @foreach($reviewerNotes as $note)
                        <article class="review-note">
                            <div class="review-note-icon">
                                <i class="bi bi-chat-square-text"></i>
                            </div>

                            <div>
                                <div class="review-note-header">
                                    <strong>{{ $note->title ?? 'Reviewer Note' }}</strong>

                                    <span class="review-note-meta">
                                        {{ $note->creator?->name ?? 'Reviewer' }}
                                        · {{ $note->created_at?->format('M d, Y h:i A') }}
                                    </span>
                                </div>

                                <div class="review-note-body">
                                    {{ $note->body }}
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="review-empty">
                    <div class="review-empty-icon">
                        <i class="bi bi-chat-left"></i>
                    </div>

                    <h3>No reviewer notes yet</h3>
                    <p>Authorized reviewer-summary notes will appear here.</p>
                </div>
            @endif
        </div>
    </section>
</div>

@if($access->can_make_decision)
    <div
        class="review-modal"
        id="reviewDecisionModal"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="reviewDecisionTitle"
    >
        <button
            type="button"
            class="review-modal-backdrop"
            data-decision-close
            aria-label="Close decision dialog"
        ></button>

        <div class="review-modal-dialog">
            <form method="POST" id="reviewDecisionForm" action="">
                @csrf

                <header class="review-modal-header">
                    <div class="review-modal-heading">
                        <div class="review-modal-icon" id="reviewDecisionIcon">
                            <i class="bi bi-check2-square"></i>
                        </div>

                        <div>
                            <h2 id="reviewDecisionTitle">Confirm review decision</h2>
                            <p id="reviewDecisionSubtitle">Authorized case action</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="review-modal-close"
                        data-decision-close
                        aria-label="Close decision dialog"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>

                <div class="review-modal-body">
                    <div class="review-modal-notice">
                        <i class="bi bi-info-circle"></i>
                        <span id="reviewDecisionMessage">
                            Review the information carefully before continuing.
                        </span>
                    </div>

                    <div class="review-field">
                        <label for="reviewDecisionRemarks" id="reviewDecisionRemarksLabel">
                            Remarks
                        </label>

                        <textarea
                            id="reviewDecisionRemarks"
                            name="remarks"
                            maxlength="5000"
                            placeholder="Add clear review remarks..."
                        ></textarea>

                        <div class="review-character-row">
                            <span id="reviewDecisionRequirementText">
                                Optional for this action
                            </span>
                            <span id="reviewDecisionCharacterCount">0 / 5000</span>
                        </div>
                    </div>
                </div>

                <footer class="review-modal-footer">
                    <button type="button" class="review-btn" data-decision-close>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="review-btn is-primary"
                        id="reviewDecisionSubmit"
                    >
                        <i class="bi bi-check2-circle"></i>
                        <span id="reviewDecisionSubmitText">Confirm decision</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
@endif

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabs = Array.from(document.querySelectorAll("[data-review-tab]"));
    const panels = Array.from(document.querySelectorAll("[data-review-panel]"));
    const externalTabButtons = Array.from(
        document.querySelectorAll("[data-review-tab-open]")
    );

    function activateReviewTab(name, focusTab = false) {
        const targetTab = tabs.find(
            (tab) => tab.dataset.reviewTab === name
        );

        const targetPanel = panels.find(
            (panel) => panel.dataset.reviewPanel === name
        );

        if (!targetTab || !targetPanel) {
            return;
        }

        tabs.forEach((tab) => {
            const active = tab === targetTab;
            tab.classList.toggle("is-active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
            tab.tabIndex = active ? 0 : -1;
        });

        panels.forEach((panel) => {
            const active = panel === targetPanel;
            panel.classList.toggle("is-active", active);
            panel.hidden = !active;
        });

        if (focusTab) {
            targetTab.focus();
        }

        try {
            const url = new URL(window.location.href);
            url.searchParams.set("section", name);
            window.history.replaceState({}, "", url);
        } catch (error) {
            // URL state is optional.
        }
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", function () {
            activateReviewTab(tab.dataset.reviewTab || "overview");
        });

        tab.addEventListener("keydown", function (event) {
            if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) {
                return;
            }

            event.preventDefault();

            let nextIndex = index;

            if (event.key === "ArrowRight") {
                nextIndex = (index + 1) % tabs.length;
            }

            if (event.key === "ArrowLeft") {
                nextIndex = (index - 1 + tabs.length) % tabs.length;
            }

            if (event.key === "Home") {
                nextIndex = 0;
            }

            if (event.key === "End") {
                nextIndex = tabs.length - 1;
            }

            activateReviewTab(
                tabs[nextIndex].dataset.reviewTab || "overview",
                true
            );
        });
    });

    let pendingDocumentFilter = null;

    externalTabButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const name = button.dataset.reviewTabOpen || "overview";

            pendingDocumentFilter =
                button.dataset.documentFilterTarget || null;

            activateReviewTab(name);

            document.querySelector(".review-shell")?.scrollIntoView({
                behavior: window.matchMedia(
                    "(prefers-reduced-motion: reduce)"
                ).matches ? "auto" : "smooth",
                block: "start"
            });

            if (name === "documents" && pendingDocumentFilter) {
                window.setTimeout(() => {
                    setDocumentFilter(pendingDocumentFilter);
                    pendingDocumentFilter = null;
                }, 50);
            }
        });
    });

    let requestedSection = "overview";

    try {
        requestedSection =
            new URL(window.location.href).searchParams.get("section")
            || "overview";
    } catch (error) {
        requestedSection = "overview";
    }

    if (!tabs.some((tab) => tab.dataset.reviewTab === requestedSection)) {
        requestedSection = "overview";
    }

    activateReviewTab(requestedSection);

    const documentCards = Array.from(
        document.querySelectorAll("[data-review-document]")
    );

    const documentSearch = document.getElementById("reviewDocumentSearch");
    const documentSearchClear = document.getElementById(
        "reviewDocumentSearchClear"
    );

    const documentFilterButtons = Array.from(
        document.querySelectorAll("[data-document-filter]")
    );

    const visibleDocumentCount = document.getElementById(
        "reviewVisibleDocumentCount"
    );

    const noDocumentResults = document.getElementById(
        "reviewDocumentNoResults"
    );

    let activeDocumentFilter = "all";

    function normalizeReviewText(value) {
        return String(value || "")
            .toLowerCase()
            .replace(/\s+/g, " ")
            .trim();
    }

    function documentMatchesFilter(card) {
        if (activeDocumentFilter === "all") {
            return true;
        }

        if (activeDocumentFilter === "attention") {
            return card.dataset.attention === "1";
        }

        if (activeDocumentFilter === "review") {
            return card.dataset.readyReview === "1";
        }

        if (activeDocumentFilter === "verified") {
            return card.dataset.status === "verified";
        }

        if (["parent", "child", "case"].includes(activeDocumentFilter)) {
            return card.dataset.scope === activeDocumentFilter;
        }

        return true;
    }

    function applyDocumentFilters() {
        if (!documentCards.length) {
            return;
        }

        const term = normalizeReviewText(documentSearch?.value);
        let count = 0;

        documentCards.forEach((card) => {
            const searchable = normalizeReviewText(card.dataset.search);
            const matchesSearch = !term || searchable.includes(term);
            const matchesFilter = documentMatchesFilter(card);
            const visible = matchesSearch && matchesFilter;

            card.hidden = !visible;

            if (visible) {
                count += 1;
            }
        });

        if (visibleDocumentCount) {
            visibleDocumentCount.textContent = String(count);
        }

        if (noDocumentResults) {
            noDocumentResults.classList.toggle(
                "is-visible",
                count === 0
            );
        }

        if (documentSearchClear) {
            documentSearchClear.classList.toggle(
                "is-visible",
                Boolean(documentSearch?.value)
            );
        }
    }

    function setDocumentFilter(name) {
        const filterExists = documentFilterButtons.some(
            (button) => button.dataset.documentFilter === name
        );

        activeDocumentFilter = filterExists ? name : "all";

        documentFilterButtons.forEach((button) => {
            const active =
                button.dataset.documentFilter === activeDocumentFilter;

            button.classList.toggle("is-active", active);
            button.setAttribute("aria-pressed", active ? "true" : "false");
        });

        applyDocumentFilters();
    }

    documentFilterButtons.forEach((button) => {
        button.addEventListener("click", function () {
            setDocumentFilter(button.dataset.documentFilter || "all");
        });
    });

    documentSearch?.addEventListener("input", applyDocumentFilters);

    documentSearchClear?.addEventListener("click", function () {
        if (!documentSearch) {
            return;
        }

        documentSearch.value = "";
        documentSearch.focus();
        applyDocumentFilters();
    });

    document.querySelectorAll("[data-review-details-toggle]").forEach((button) => {
        button.addEventListener("click", function () {
            const id = button.dataset.reviewDetailsToggle;
            const details = document.getElementById(
                "reviewDocumentDetails" + id
            );

            if (!details) {
                return;
            }

            const willOpen = details.hidden;
            details.hidden = !willOpen;
            button.setAttribute(
                "aria-expanded",
                willOpen ? "true" : "false"
            );

            const icon = button.querySelector("i");

            if (icon) {
                icon.className = willOpen
                    ? "bi bi-chevron-up"
                    : "bi bi-info-circle";
            }

            const textNodes = Array.from(button.childNodes)
                .filter((node) => node.nodeType === Node.TEXT_NODE);

            if (textNodes.length) {
                textNodes[textNodes.length - 1].textContent =
                    willOpen ? " Hide details" : " Details";
            }
        });
    });

    applyDocumentFilters();

    const noteBody = document.getElementById("review_note_body");
    const noteCount = document.getElementById(
        "reviewNoteCharacterCount"
    );

    function updateNoteCount() {
        if (!noteBody || !noteCount) {
            return;
        }

        noteCount.textContent =
            `${noteBody.value.length} / 5000`;
    }

    noteBody?.addEventListener("input", updateNoteCount);
    updateNoteCount();

    const modal = document.getElementById("reviewDecisionModal");
    const decisionForm = document.getElementById("reviewDecisionForm");
    const decisionTitle = document.getElementById("reviewDecisionTitle");
    const decisionSubtitle = document.getElementById(
        "reviewDecisionSubtitle"
    );
    const decisionMessage = document.getElementById(
        "reviewDecisionMessage"
    );
    const decisionIcon = document.getElementById("reviewDecisionIcon");
    const decisionRemarks = document.getElementById(
        "reviewDecisionRemarks"
    );
    const decisionRequirementText = document.getElementById(
        "reviewDecisionRequirementText"
    );
    const decisionCharacterCount = document.getElementById(
        "reviewDecisionCharacterCount"
    );
    const decisionSubmit = document.getElementById(
        "reviewDecisionSubmit"
    );
    const decisionSubmitText = document.getElementById(
        "reviewDecisionSubmitText"
    );

    let lastFocusedElement = null;

    const decisionModes = {
        "approve-case": {
            icon: "bi-check2-circle",
            buttonClass: "review-btn is-success",
            buttonText: "Approve case review",
            placeholder: "Add optional approval remarks..."
        },
        "request-changes": {
            icon: "bi-arrow-counterclockwise",
            buttonClass: "review-btn is-warning",
            buttonText: "Request changes",
            placeholder: "Explain what must be corrected or completed..."
        },
        "accept-document": {
            icon: "bi-file-earmark-check",
            buttonClass: "review-btn is-success",
            buttonText: "Accept document",
            placeholder: "Add optional acceptance remarks..."
        },
        "reject-document": {
            icon: "bi-file-earmark-x",
            buttonClass: "review-btn is-danger",
            buttonText: "Return for revision",
            placeholder: "Explain why the submitted file needs revision..."
        }
    };

    function updateDecisionCharacterCount() {
        if (!decisionRemarks || !decisionCharacterCount) {
            return;
        }

        decisionCharacterCount.textContent =
            `${decisionRemarks.value.length} / 5000`;
    }

    function openDecisionModal(button) {
        if (!modal || !decisionForm) {
            return;
        }

        lastFocusedElement = document.activeElement;

        const mode = button.dataset.decisionMode || "approve-case";
        const modeConfig = decisionModes[mode] || decisionModes["approve-case"];
        const remarksRequired = button.dataset.decisionRequired === "1";

        decisionForm.action = button.dataset.decisionAction || "";

        if (decisionTitle) {
            decisionTitle.textContent =
                button.dataset.decisionTitle || "Confirm review decision";
        }

        if (decisionSubtitle) {
            decisionSubtitle.textContent =
                button.dataset.decisionSubtitle || "Authorized case action";
        }

        if (decisionMessage) {
            decisionMessage.textContent =
                button.dataset.decisionMessage
                || "Review the information carefully before continuing.";
        }

        if (decisionIcon) {
            decisionIcon.innerHTML =
                `<i class="bi ${modeConfig.icon}"></i>`;
        }

        if (decisionRemarks) {
            decisionRemarks.value = "";
            decisionRemarks.required = remarksRequired;
            decisionRemarks.placeholder = modeConfig.placeholder;
        }

        if (decisionRequirementText) {
            decisionRequirementText.textContent = remarksRequired
                ? "Remarks are required for this action"
                : "Remarks are optional for this action";
        }

        if (decisionSubmit) {
            decisionSubmit.className = modeConfig.buttonClass;
            decisionSubmit.disabled = false;
        }

        if (decisionSubmitText) {
            decisionSubmitText.textContent = modeConfig.buttonText;
        }

        updateDecisionCharacterCount();

        modal.hidden = false;
        document.body.classList.add("review-modal-open");

        window.setTimeout(() => {
            if (remarksRequired) {
                decisionRemarks?.focus();
            } else {
                decisionSubmit?.focus();
            }
        }, 50);
    }

    function closeDecisionModal() {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove("review-modal-open");

        if (decisionForm) {
            decisionForm.action = "";
        }

        if (decisionRemarks) {
            decisionRemarks.value = "";
            decisionRemarks.required = false;
        }

        updateDecisionCharacterCount();
        lastFocusedElement?.focus?.();
    }

    document.querySelectorAll("[data-decision-open]").forEach((button) => {
        button.addEventListener("click", function () {
            openDecisionModal(button);
        });
    });

    document.querySelectorAll("[data-decision-close]").forEach((button) => {
        button.addEventListener("click", closeDecisionModal);
    });

    decisionRemarks?.addEventListener(
        "input",
        updateDecisionCharacterCount
    );

    decisionForm?.addEventListener("submit", function () {
        if (decisionSubmit) {
            decisionSubmit.disabled = true;
        }

        if (decisionSubmitText) {
            decisionSubmitText.textContent = "Submitting...";
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal && !modal.hidden) {
            closeDecisionModal();
        }
    });
});
</script>
@endsection
