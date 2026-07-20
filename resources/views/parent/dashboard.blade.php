@extends('layouts.dashboard', ['title' => 'Parent Dashboard'])

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Safe Parent User
    |--------------------------------------------------------------------------
    */

    $currentParent = isset($parentUser) && $parentUser instanceof \App\Models\User
        ? $parentUser
        : auth()->user();

    $applicationStatus = $adoptionCase?->status_label ?? 'No Active Case';

    $statusClass = match ($adoptionCase?->status) {
        'document_collection' => 'status-yellow',
        'assessment' => 'status-blue',
        'matching_review' => 'status-purple',
        'placement',
        'supervised_trial_custody' => 'status-orange',
        'finalized' => 'status-green',
        'cancelled' => 'status-red',
        default => 'status-gray',
    };

    $profileUrl = \Illuminate\Support\Facades\Route::has('parent.application.edit')
        ? route('parent.application.edit')
        : (
            \Illuminate\Support\Facades\Route::has('parent.application.index')
                ? route('parent.application.index')
                : '#'
        );

    $documentsUrl = \Illuminate\Support\Facades\Route::has('parent.documents.index')
        ? route('parent.documents.index')
        : (
            \Illuminate\Support\Facades\Route::has('parent.application.documents')
                ? route('parent.application.documents')
                : '#documents'
        );

    $aiGuidanceUrl = \Illuminate\Support\Facades\Route::has('parent.ai.index')
        ? route('parent.ai.index')
        : '#';

    $pendingDocumentsCount = max(
        0,
        $requiredDocumentsCount - $submittedDocumentsCount
    );

    $documentsNeedingAttention = $requiredDocuments
        ->whereIn('status', ['rejected', 'expired'])
        ->count();
@endphp

<style>
    :root {
        --parent-primary: #8f2f17;
        --parent-orange: #e85d24;
        --parent-dark: #28140d;
        --parent-cream: #fff8f2;
        --parent-border: #ead8cb;
        --parent-muted: #74645b;
        --parent-white: #ffffff;

        --green-bg: #dcfce7;
        --green-text: #166534;

        --blue-bg: #dbeafe;
        --blue-text: #1d4ed8;

        --yellow-bg: #fef3c7;
        --yellow-text: #92400e;

        --red-bg: #fee2e2;
        --red-text: #991b1b;

        --purple-bg: #ede9fe;
        --purple-text: #6d28d9;

        --gray-bg: #f1f5f9;
        --gray-text: #475569;

        --shadow: 0 12px 28px rgba(71, 34, 19, 0.07);
    }

    .parent-dashboard {
        display: grid;
        gap: 18px;
    }

    .dashboard-alert {
        padding: 14px 16px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 800;
    }

    .dashboard-alert-success {
        background: var(--green-bg);
        color: var(--green-text);
        border: 1px solid #86efac;
    }

    .dashboard-alert-error {
        background: var(--red-bg);
        color: var(--red-text);
        border: 1px solid #fca5a5;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: center;
        flex-wrap: wrap;
        padding: 22px;
        background: linear-gradient(135deg, #fff7ed, #ffffff);
        border: 1px solid #fed7aa;
        border-radius: 20px;
        box-shadow: var(--shadow);
    }

    .dashboard-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: var(--parent-primary);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .dashboard-header h1 {
        margin: 0;
        color: var(--parent-dark);
        font-size: clamp(25px, 3vw, 34px);
        font-weight: 900;
    }

    .dashboard-header p {
        margin: 7px 0 0;
        color: var(--parent-muted);
        font-size: 14px;
        line-height: 1.5;
    }

    .secure-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: var(--green-bg);
        color: var(--green-text);
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .privacy-notice {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 14px 16px;
        border: 1px solid #fde68a;
        border-radius: 15px;
        background: #fffbeb;
        color: #92400e;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.55;
    }

    .privacy-notice i {
        margin-top: 2px;
        font-size: 18px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .stat-card {
        position: relative;
        min-height: 132px;
        padding: 18px;
        overflow: hidden;
        border: 1px solid var(--parent-border);
        border-radius: 18px;
        background: var(--parent-white);
        box-shadow: var(--shadow);
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        margin-bottom: 13px;
        border-radius: 12px;
        background: #ffedd5;
        color: var(--parent-primary);
        font-size: 18px;
    }

    .stat-label {
        color: var(--parent-muted);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .stat-value {
        margin-top: 6px;
        color: var(--parent-dark);
        font-size: 30px;
        font-weight: 900;
        line-height: 1.1;
    }

    .stat-value.status-value {
        font-size: 18px;
        line-height: 1.35;
    }

    .stat-help {
        margin-top: 6px;
        color: var(--parent-muted);
        font-size: 12px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 7px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .status-green {
        background: var(--green-bg);
        color: var(--green-text);
    }

    .status-blue {
        background: var(--blue-bg);
        color: var(--blue-text);
    }

    .status-yellow {
        background: var(--yellow-bg);
        color: var(--yellow-text);
    }

    .status-red {
        background: var(--red-bg);
        color: var(--red-text);
    }

    .status-purple {
        background: var(--purple-bg);
        color: var(--purple-text);
    }

    .status-orange {
        background: #ffedd5;
        color: #c2410c;
    }

    .status-gray {
        background: var(--gray-bg);
        color: var(--gray-text);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.6fr);
        gap: 18px;
        align-items: start;
    }

    .dashboard-panel {
        padding: 20px;
        border: 1px solid var(--parent-border);
        border-radius: 19px;
        background: var(--parent-white);
        box-shadow: var(--shadow);
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .dashboard-panel h2 {
        margin: 0;
        color: var(--parent-dark);
        font-size: 18px;
        font-weight: 900;
    }

    .dashboard-panel-description {
        margin: 5px 0 0;
        color: var(--parent-muted);
        font-size: 13px;
        line-height: 1.5;
    }

    .case-code {
        display: inline-flex;
        padding: 7px 10px;
        border-radius: 999px;
        background: #ffedd5;
        color: var(--parent-primary);
        font-size: 11px;
        font-weight: 900;
    }

    .progress-summary {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 17px;
        align-items: center;
    }

    .progress-circle {
        width: 96px;
        height: 96px;
        display: grid;
        place-items: center;
        flex: 0 0 96px;
        border-radius: 50%;
        background:
            conic-gradient(
                var(--parent-orange)
                {{ min(100, max(0, $documentProgressPercent)) }}%,
                #eee4dd 0
            );
        position: relative;
    }

    .progress-circle::before {
        content: "";
        position: absolute;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: #ffffff;
    }

    .progress-circle strong {
        position: relative;
        z-index: 1;
        color: var(--parent-dark);
        font-size: 20px;
        font-weight: 900;
    }

    .progress-details {
        display: grid;
        gap: 9px;
    }

    .progress-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0e5de;
    }

    .progress-item:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }

    .progress-item span {
        color: var(--parent-muted);
        font-size: 13px;
    }

    .progress-item strong {
        color: var(--parent-dark);
        font-size: 13px;
    }

    .quick-actions {
        display: grid;
        gap: 10px;
    }

    .quick-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px;
        border: 1px solid #ead8cb;
        border-radius: 14px;
        background: var(--parent-cream);
        color: inherit;
        text-decoration: none;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .quick-action:hover {
        transform: translateY(-2px);
        border-color: #fb923c;
    }

    .quick-action-icon {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        flex: 0 0 39px;
        border-radius: 11px;
        background: var(--parent-primary);
        color: #ffffff;
    }

    .quick-action strong {
        display: block;
        color: var(--parent-dark);
        font-size: 13px;
    }

    .quick-action small {
        display: block;
        margin-top: 3px;
        color: var(--parent-muted);
        font-size: 11px;
        line-height: 1.35;
    }

    .recent-updates {
        display: grid;
        gap: 10px;
    }

    .update-card {
        padding: 13px 14px;
        border-left: 4px solid var(--parent-orange);
        border-radius: 11px;
        background: #fff8f2;
    }

    .update-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }

    .update-header strong {
        color: var(--parent-dark);
        font-size: 13px;
    }

    .update-header small {
        color: var(--parent-muted);
        font-size: 10px;
        white-space: nowrap;
    }

    .update-card p {
        margin: 7px 0 0;
        color: #5f5048;
        font-size: 12px;
        line-height: 1.5;
    }

    .empty-state {
        padding: 24px 18px;
        border: 1px dashed #d8c7bc;
        border-radius: 15px;
        background: var(--parent-cream);
        text-align: center;
        color: var(--parent-muted);
        font-size: 13px;
        line-height: 1.55;
    }

    .empty-state i {
        display: block;
        margin-bottom: 8px;
        color: var(--parent-primary);
        font-size: 28px;
    }

    @media (max-width: 1050px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            padding: 18px;
        }

        .secure-badge {
            width: 100%;
            justify-content: center;
        }

        .progress-summary {
            grid-template-columns: 1fr;
        }

        .progress-circle {
            margin: 0 auto;
        }

        .update-header {
            flex-direction: column;
        }
    }
</style>

<div class="parent-dashboard">
    @if(session('success'))
        <div class="dashboard-alert dashboard-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="dashboard-alert dashboard-alert-error">
            {{ session('error') }}
        </div>
    @endif

    <section class="dashboard-header">
        <div>
            <div class="dashboard-eyebrow">
                <i class="bi bi-house-heart"></i>
                Parent Portal
            </div>

            <h1>
                Welcome, {{ $currentParent?->name ?? 'Parent Applicant' }}
            </h1>

            <p>
                View a quick summary of your application and required documents.
            </p>
        </div>

        <div class="secure-badge">
            <i class="bi bi-shield-check"></i>
            Secure Applicant Portal
        </div>
    </section>

    <div class="privacy-notice">
        <i class="bi bi-shield-lock"></i>

        <div>
            Child profiles, matching rankings, confidential records, and placement
            recommendations are restricted for privacy and child protection.
        </div>
    </div>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-clipboard-check"></i>
            </div>

            <div class="stat-label">Application Status</div>

            <div class="stat-value status-value">
                <span class="status-pill {{ $statusClass }}">
                    {{ $applicationStatus }}
                </span>
            </div>

            @if($adoptionCase)
                <div class="stat-help">
                    {{ $adoptionCase->case_code }}
                </div>
            @endif
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-files"></i>
            </div>

            <div class="stat-label">Required Documents</div>

            <div class="stat-value">
                {{ $requiredDocumentsCount }}
            </div>

            <div class="stat-help">
                Total parent requirements
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-cloud-check"></i>
            </div>

            <div class="stat-label">Submitted Documents</div>

            <div class="stat-value">
                {{ $submittedDocumentsCount }}
            </div>

            <div class="stat-help">
                {{ $pendingDocumentsCount }} still pending
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-patch-check"></i>
            </div>

            <div class="stat-label">Verified Documents</div>

            <div class="stat-value">
                {{ $verifiedDocumentsCount }}
            </div>

            <div class="stat-help">
                {{ $documentProgressPercent }}% complete
            </div>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="dashboard-panel">
            <div class="panel-header">
                <div>
                    <h2>Document Progress</h2>

                    <p class="dashboard-panel-description">
                        Summary of your parent-side document requirements.
                    </p>
                </div>

                @if($adoptionCase)
                    <span class="case-code">
                        {{ $adoptionCase->case_code }}
                    </span>
                @endif
            </div>

            @if($adoptionCase && $requiredDocumentsCount > 0)
                <div class="progress-summary">
                    <div class="progress-circle">
                        <strong>{{ $documentProgressPercent }}%</strong>
                    </div>

                    <div class="progress-details">
                        <div class="progress-item">
                            <span>Total required</span>
                            <strong>{{ $requiredDocumentsCount }}</strong>
                        </div>

                        <div class="progress-item">
                            <span>Submitted</span>
                            <strong>{{ $submittedDocumentsCount }}</strong>
                        </div>

                        <div class="progress-item">
                            <span>Verified</span>
                            <strong>{{ $verifiedDocumentsCount }}</strong>
                        </div>

                        <div class="progress-item">
                            <span>Waiting for submission</span>
                            <strong>{{ $pendingDocumentsCount }}</strong>
                        </div>

                        <div class="progress-item">
                            <span>Needs attention</span>
                            <strong>{{ $documentsNeedingAttention }}</strong>
                        </div>
                    </div>
                </div>
            @elseif($adoptionCase)
                <div class="empty-state">
                    <i class="bi bi-file-earmark"></i>
                    Your parent document checklist has not been assigned yet.
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    No active adoption case has been assigned to your account yet.
                </div>
            @endif
        </div>

        <aside class="dashboard-panel">
            <div class="panel-header">
                <div>
                    <h2>Quick Actions</h2>

                    <p class="dashboard-panel-description">
                        Continue your application.
                    </p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="{{ $profileUrl }}" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>

                    <div>
                        <strong>My Application</strong>
                        <small>Review or update your information.</small>
                    </div>
                </a>

                <a href="{{ $documentsUrl }}" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>

                    <div>
                        <strong>Upload Documents</strong>
                        <small>Submit your required files.</small>
                    </div>
                </a>

                <a href="{{ $aiGuidanceUrl }}" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="bi bi-chat-square-text"></i>
                    </div>

                    <div>
                        <strong>AI Legal Guidance</strong>
                        <small>Ask adoption-related questions.</small>
                    </div>
                </a>
            </div>
        </aside>
    </section>

    <section class="dashboard-panel">
        <div class="panel-header">
            <div>
                <h2>Recent Updates</h2>

                <p class="dashboard-panel-description">
                    Latest official updates shared by authorized staff.
                </p>
            </div>
        </div>

        @if($parentUpdates->count())
            <div class="recent-updates">
                @foreach($parentUpdates->take(3) as $update)
                    <article class="update-card">
                        <div class="update-header">
                            <strong>
                                {{ $update->title ?? $update->note_type_label ?? 'Case Update' }}
                            </strong>

                            <small>
                                {{ $update->created_at?->format('M d, Y h:i A') }}
                            </small>
                        </div>

                        <p>
                            {{ \Illuminate\Support\Str::limit($update->body, 180) }}
                        </p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                No official updates are available yet.
            </div>
        @endif
    </section>
</div>
@endsection