@extends('layouts.dashboard', ['title' => 'Adoption Case Details'])

@section('content')
@php
    $totalDocuments = $adoptionCase->documents->count();
    $verifiedDocuments = $adoptionCase->documents->where('status', 'verified')->count();
    $submittedDocuments = $adoptionCase->documents->whereIn('status', ['submitted', 'under_review', 'verified'])->count();

    $documentProgressPercent = $totalDocuments > 0
        ? round(($verifiedDocuments / $totalDocuments) * 100)
        : 0;

    $parentDocuments = $adoptionCase->documents->where('requirement_scope', 'parent')->count();
    $childDocuments = $adoptionCase->documents->where('requirement_scope', 'child')->count();

    $statusClass = match ($adoptionCase->status) {
        'draft' => 'case-status-gray',
        'document_collection' => 'case-status-blue',
        'assessment' => 'case-status-purple',
        'matching_review' => 'case-status-yellow',
        'pre_placement' => 'case-status-orange',
        'placement_supervision' => 'case-status-indigo',
        'court_process' => 'case-status-pink',
        'finalized' => 'case-status-green',
        'closed' => 'case-status-gray',
        'cancelled' => 'case-status-red',
        default => 'case-status-gray',
    };
@endphp

<style>
    .case-page {
        max-width: 100%;
        overflow-x: hidden;
    }

    .case-topbar {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px 22px;
        margin-bottom: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }

    .case-topbar-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .case-breadcrumb {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .case-breadcrumb a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 800;
    }

    .case-title {
        margin: 0;
        color: #0f172a;
        font-size: 26px;
        font-weight: 900;
    }

    .case-subtitle {
        margin-top: 6px;
        color: #64748b;
        line-height: 1.5;
    }

    .case-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .case-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        padding: 10px 14px;
        font-weight: 800;
        font-size: 14px;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
    }

    .case-btn-primary {
        background: #2563eb;
        color: #ffffff;
    }

    .case-btn-primary:hover {
        background: #1d4ed8;
    }

    .case-btn-light {
        background: #ffffff;
        color: #334155;
        border-color: #dbe3ef;
    }

    .case-btn-light:hover {
        background: #f8fafc;
    }

    .case-nav {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    .case-nav a {
        padding: 9px 13px;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        border: 1px solid #e5e7eb;
    }

    .case-nav a:hover {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }

    .case-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        margin-bottom: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }

    .case-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .case-summary-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        background: #f8fafc;
    }

    .case-summary-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .case-summary-value {
        color: #0f172a;
        font-weight: 900;
        font-size: 15px;
        line-height: 1.4;
    }

    .case-status-badge {
        display: inline-flex;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 900;
    }

    .case-status-gray {
        background: #f1f5f9;
        color: #475569;
    }

    .case-status-blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .case-status-purple {
        background: #ede9fe;
        color: #6d28d9;
    }

    .case-status-yellow {
        background: #fef3c7;
        color: #92400e;
    }

    .case-status-orange {
        background: #ffedd5;
        color: #9a3412;
    }

    .case-status-indigo {
        background: #e0e7ff;
        color: #4338ca;
    }

    .case-status-pink {
        background: #fce7f3;
        color: #be185d;
    }

    .case-status-green {
        background: #dcfce7;
        color: #166534;
    }

    .case-status-red {
        background: #fee2e2;
        color: #991b1b;
    }

    .document-header-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .document-eyebrow {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 10px;
    }

    .document-title {
        margin: 0;
        font-size: 24px;
        font-weight: 900;
        color: #0f172a;
    }

    .document-subtitle {
        margin: 8px 0 0;
        color: #64748b;
        line-height: 1.6;
        max-width: 720px;
    }

    .progress-card {
        width: 280px;
        max-width: 100%;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
    }

    .progress-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 10px;
    }

    .progress-label {
        color: #475569;
        font-size: 13px;
        font-weight: 900;
    }

    .progress-percent {
        color: #0f172a;
        font-size: 24px;
        font-weight: 900;
    }

    .progress-track {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        width: {{ $documentProgressPercent }}%;
        background: linear-gradient(90deg, #2563eb, #22c55e);
        border-radius: 999px;
    }

    .progress-meta {
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .document-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .document-stat {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
    }

    .document-stat-value {
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .document-stat-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 800;
    }

    .document-table-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
        margin-top: 18px;
    }

    .document-table-heading {
        padding: 18px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .document-table-heading h3 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
    }

    .document-table-heading p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .document-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .document-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
    }

    .document-table th {
        background: #ffffff;
        color: #475569;
        text-align: left;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .document-table td {
        padding: 16px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: top;
        color: #334155;
    }

    .document-table tbody tr:hover {
        background: #f8fafc;
    }

    .document-table th:last-child,
    .document-table td:last-child {
        position: sticky;
        right: 0;
        background: inherit;
        z-index: 2;
        box-shadow: -10px 0 18px rgba(15, 23, 42, 0.04);
    }

    .doc-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 300px;
    }

    .doc-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        flex: 0 0 auto;
        font-size: 12px;
    }

    .doc-title {
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .doc-type {
        color: #64748b;
        font-size: 13px;
    }

    .scope-badge,
    .status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .scope-parent {
        background: #fef3c7;
        color: #92400e;
    }

    .scope-child {
        background: #ede9fe;
        color: #5b21b6;
    }

    .scope-case {
        background: #e0f2fe;
        color: #075985;
    }

    .status-pending {
        background: #f1f5f9;
        color: #475569;
    }

    .status-submitted {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-under-review {
        background: #fef3c7;
        color: #92400e;
    }

    .status-verified {
        background: #dcfce7;
        color: #166534;
    }

    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-expired {
        background: #ffedd5;
        color: #9a3412;
    }

    .status-not-required {
        background: #f3f4f6;
        color: #4b5563;
    }

    .small-muted {
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .file-card {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: 14px;
        padding: 10px 12px;
        max-width: 260px;
    }

    .file-name {
        color: #0f172a;
        font-weight: 900;
        word-break: break-word;
        font-size: 13px;
    }

    .empty-text {
        color: #94a3b8;
        font-size: 13px;
        font-weight: 700;
    }

    .remarks-box {
        max-width: 280px;
        line-height: 1.5;
    }

    .update-btn {
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        padding: 10px 14px;
        font-weight: 900;
        cursor: pointer;
        white-space: nowrap;
    }

    .update-btn:hover {
        background: #1d4ed8;
    }

    .light-btn {
        background: #ffffff;
        color: #334155;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        padding: 10px 14px;
        font-weight: 900;
        cursor: pointer;
    }

    .document-form-row td {
        background: #f8fafc !important;
        padding: 0 !important;
    }

    .document-update-card {
        margin: 16px;
        padding: 20px;
        border: 1px solid #bfdbfe;
        border-radius: 18px;
        background: #ffffff;
    }

    .document-update-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .document-update-header h4 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
    }

    .document-update-header p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .document-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .form-field label {
        display: block;
        margin-bottom: 7px;
        color: #334155;
        font-size: 13px;
        font-weight: 900;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 11px 12px;
        color: #0f172a;
        outline: none;
        background: #ffffff;
    }

    .form-field textarea {
        resize: vertical;
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .field-help {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 18px;
        flex-wrap: wrap;
    }

    .note-list {
        display: grid;
        gap: 12px;
    }

    .note-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        background: #f8fafc;
    }

    .note-title {
        color: #0f172a;
        font-weight: 900;
        margin-bottom: 5px;
    }

    .note-meta {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .note-body {
        color: #334155;
        line-height: 1.6;
    }

    .empty-state {
        text-align: center;
        padding: 36px 18px;
        color: #64748b;
    }

    @media (max-width: 1000px) {
        .case-summary-grid,
        .document-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .progress-card {
            width: 100%;
        }
    }

    @media (max-width: 700px) {
        .case-section,
        .case-topbar {
            padding: 16px;
        }

        .case-summary-grid,
        .document-stats,
        .document-form-grid {
            grid-template-columns: 1fr;
        }

        .case-title {
            font-size: 22px;
        }

        .document-title {
            font-size: 21px;
        }
    }
</style>

<div class="case-page">

    <div class="case-topbar">
        <div class="case-topbar-row">
            <div>
                <div class="case-breadcrumb">
                    <a href="{{ route('admin.adoption-cases.index') }}">Adoption Cases</a>
                    / {{ $adoptionCase->case_code }}
                </div>

                <h1 class="case-title">
                    Adoption Case {{ $adoptionCase->case_code }}
                </h1>

                <div class="case-subtitle">
                    Manage case information, document checklist, parent requirements, child requirements, and case notes.
                </div>
            </div>

            <div class="case-actions">
                <a href="{{ route('admin.adoption-cases.index') }}" class="case-btn case-btn-light">
                    Back to List
                </a>

                <a href="{{ route('admin.adoption-cases.edit', $adoptionCase) }}" class="case-btn case-btn-primary">
                    Edit Case
                </a>
            </div>
        </div>

        <div class="case-nav">
            <a href="#case-overview">Overview</a>
            <a href="#documents">Documents</a>
            <a href="#case-notes">Case Notes</a>
        </div>
    </div>

    <div class="case-section" id="case-overview">
        <div class="case-summary-grid">
            <div class="case-summary-card">
                <div class="case-summary-label">Child</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->child?->full_name ?? 'N/A' }}
                    <br>
                    <small>{{ $adoptionCase->child?->child_code ?? '' }}</small>
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Prospective Parent</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->prospectiveParent?->name ?? 'Not assigned' }}
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Assigned Staff</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->assignedSocialWorker?->name ?? 'Not assigned' }}
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Status</div>
                <div class="case-summary-value">
                    <span class="case-status-badge {{ $statusClass }}">
                        {{ $adoptionCase->status_label }}
                    </span>
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Case Type</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->case_type_label }}
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Priority</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->priority_label }}
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Opened At</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->opened_at?->format('M d, Y') ?? 'N/A' }}
                </div>
            </div>

            <div class="case-summary-card">
                <div class="case-summary-label">Target Completion</div>
                <div class="case-summary-value">
                    {{ $adoptionCase->target_completion_date?->format('M d, Y') ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <div class="case-section" id="documents">
        <div class="document-header-row">
            <div>
                <div class="document-eyebrow">Document Management</div>

                <h2 class="document-title">Document Checklist</h2>

                <p class="document-subtitle">
                    Review parent and child requirements, upload supporting files when needed,
                    update verification status, and keep remarks clear for case monitoring.
                </p>
            </div>

            <div class="progress-card">
                <div class="progress-top">
                    <div>
                        <div class="progress-label">Verification Progress</div>
                        <div class="progress-meta">
                            {{ $verifiedDocuments }} of {{ $totalDocuments }} verified
                        </div>
                    </div>

                    <div class="progress-percent">
                        {{ $documentProgressPercent }}%
                    </div>
                </div>

                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>

                <div class="progress-meta">
                    {{ $adoptionCase->document_progress }} Verified
                </div>
            </div>
        </div>

        <div class="document-stats">
            <div class="document-stat">
                <div class="document-stat-value">{{ $totalDocuments }}</div>
                <div class="document-stat-label">Total Documents</div>
            </div>

            <div class="document-stat">
                <div class="document-stat-value">{{ $submittedDocuments }}</div>
                <div class="document-stat-label">Submitted / Reviewed</div>
            </div>

            <div class="document-stat">
                <div class="document-stat-value">{{ $parentDocuments }}</div>
                <div class="document-stat-label">Parent Requirements</div>
            </div>

            <div class="document-stat">
                <div class="document-stat-value">{{ $childDocuments }}</div>
                <div class="document-stat-label">Child Requirements</div>
            </div>
        </div>

        <div class="document-table-card">
            <div class="document-table-heading">
                <h3>Checklist Records</h3>
                <p>Use the update button to change status, attach files, or add remarks.</p>
            </div>

            <div class="document-scroll">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Scope</th>
                            <th>Status</th>
                            <th>Uploaded File</th>
                            <th>Remarks</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($adoptionCase->documents as $document)
                            @php
                                $scopeClass = match ($document->requirement_scope) {
                                    'parent' => 'scope-parent',
                                    'child' => 'scope-child',
                                    'case' => 'scope-case',
                                    default => 'scope-case',
                                };

                                $docStatusClass = match ($document->status) {
                                    'pending' => 'status-pending',
                                    'submitted' => 'status-submitted',
                                    'under_review' => 'status-under-review',
                                    'verified' => 'status-verified',
                                    'rejected' => 'status-rejected',
                                    'expired' => 'status-expired',
                                    'not_required' => 'status-not-required',
                                    default => 'status-pending',
                                };

                                $fileSize = $document->file_size
                                    ? number_format($document->file_size / 1024, 1) . ' KB'
                                    : null;
                            @endphp

                            <tr>
                                <td>
                                    <div class="doc-info">
                                        <div class="doc-icon">DOC</div>

                                        <div>
                                            <div class="doc-title">{{ $document->document_name }}</div>
                                            <div class="doc-type">{{ $document->document_type ?? 'No document type' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="scope-badge {{ $scopeClass }}">
                                        {{ $document->scope_label }}
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge {{ $docStatusClass }}">
                                        {{ $document->status_label }}
                                    </span>

                                    @if($document->verified_at)
                                        <div class="small-muted">
                                            Verified on {{ $document->verified_at?->format('M d, Y h:i A') }}
                                        </div>
                                    @endif

                                    @if($document->expiry_date)
                                        <div class="small-muted">
                                            Expires on {{ $document->expiry_date?->format('M d, Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if($document->original_filename)
                                        <div class="file-card">
                                            <div class="file-name">{{ $document->original_filename }}</div>

                                            <div class="small-muted">
                                                Uploaded by {{ $document->uploader?->name ?? 'N/A' }}
                                                @if($fileSize)
                                                    <br>Size: {{ $fileSize }}
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="empty-text">No file uploaded</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="remarks-box">
                                        {{ $document->remarks ?? 'No remarks' }}
                                    </div>
                                </td>

                                <td style="text-align: right;">
                                    <button
                                        type="button"
                                        class="update-btn"
                                        onclick="toggleDocumentForm({{ $document->id }})"
                                    >
                                        Update
                                    </button>
                                </td>
                            </tr>

                            <tr
                                id="document-form-{{ $document->id }}"
                                class="document-form-row"
                                style="display: none;"
                            >
                                <td colspan="6">
                                    <div class="document-update-card">
                                        <div class="document-update-header">
                                            <div>
                                                <h4>Update Document</h4>
                                                <p>
                                                    Updating:
                                                    <strong>{{ $document->document_name }}</strong>
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                class="light-btn"
                                                onclick="toggleDocumentForm({{ $document->id }})"
                                            >
                                                Close
                                            </button>
                                        </div>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.adoption-cases.documents.update', [$adoptionCase, $document]) }}"
                                            enctype="multipart/form-data"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <div class="document-form-grid">
                                                <div class="form-field">
                                                    <label for="status_{{ $document->id }}">Document Status</label>
                                                    <select
                                                        id="status_{{ $document->id }}"
                                                        name="status"
                                                        required
                                                    >
                                                        @foreach($documentStatuses as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('status', $document->status) === $value)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-field">
                                                    <label for="expiry_date_{{ $document->id }}">Expiry Date</label>
                                                    <input
                                                        type="date"
                                                        id="expiry_date_{{ $document->id }}"
                                                        name="expiry_date"
                                                        value="{{ old('expiry_date', $document->expiry_date?->format('Y-m-d')) }}"
                                                    >

                                                    <small class="field-help">
                                                        Leave blank if the document does not expire.
                                                    </small>
                                                </div>

                                                <div class="form-field">
                                                    <label for="file_{{ $document->id }}">Upload / Replace File</label>
                                                    <input
                                                        type="file"
                                                        id="file_{{ $document->id }}"
                                                        name="file"
                                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                    >

                                                    <small class="field-help">
                                                        Accepted: PDF, JPG, JPEG, PNG, DOC, DOCX. Max size: 5MB.
                                                    </small>
                                                </div>

                                                <div class="form-field">
                                                    <label for="remarks_{{ $document->id }}">Remarks for Parent/Admin</label>
                                                    <textarea
                                                        id="remarks_{{ $document->id }}"
                                                        name="remarks"
                                                        rows="4"
                                                        placeholder="Example: Please upload a clearer copy."
                                                    >{{ old('remarks', $document->remarks) }}</textarea>
                                                </div>
                                            </div>

                                            <div class="form-actions">
                                                <button
                                                    type="button"
                                                    class="light-btn"
                                                    onclick="toggleDocumentForm({{ $document->id }})"
                                                >
                                                    Cancel
                                                </button>

                                                <button type="submit" class="update-btn">
                                                    Save Document Update
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        No document checklist has been generated for this adoption case.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="case-section" id="case-notes">
        <div class="document-header-row">
            <div>
                <div class="document-eyebrow">Case Notes</div>
                <h2 class="document-title">Notes and Updates</h2>
                <p class="document-subtitle">
                    Internal notes, reviewer summaries, and parent-visible updates connected to this adoption case.
                </p>
            </div>
        </div>

        <div style="margin-top: 18px;">
            <form method="POST" action="{{ route('admin.adoption-cases.notes.store', $adoptionCase) }}">
                @csrf

                <div class="document-form-grid">
                    <div class="form-field">
                        <label for="note_type">Note Type</label>
                        <select id="note_type" name="note_type" required>
                            @foreach($noteTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="visibility">Visibility</label>
                        <select id="visibility" name="visibility" required>
                            @foreach($noteVisibilities as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="title">Title</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="Example: Document Update"
                        >
                    </div>

                    <div class="form-field">
                        <label for="body">Note Body</label>
                        <textarea
                            id="body"
                            name="body"
                            rows="4"
                            placeholder="Write the note here..."
                            required
                        ></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="update-btn">
                        Add Case Note
                    </button>
                </div>
            </form>
        </div>

        <div style="margin-top: 22px;" class="note-list">
            @forelse($adoptionCase->notes as $note)
                <div class="note-card">
                    <div class="note-title">
                        {{ $note->title ?? 'Untitled Note' }}
                    </div>

                    <div class="note-meta">
                        {{ $note->note_type_label }}
                        · {{ $note->visibility_label }}
                        · {{ $note->creator?->name ?? 'N/A' }}
                        · {{ $note->created_at?->format('M d, Y h:i A') }}
                    </div>

                    <div class="note-body">
                        {{ $note->body }}
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    No case notes yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    function toggleDocumentForm(documentId) {
        const selectedRow = document.getElementById('document-form-' + documentId);

        if (!selectedRow) {
            return;
        }

        const allRows = document.querySelectorAll('.document-form-row');

        allRows.forEach(function(row) {
            if (row !== selectedRow) {
                row.style.display = 'none';
            }
        });

        selectedRow.style.display = selectedRow.style.display === 'none' ? 'table-row' : 'none';
    }
</script>
@endsection