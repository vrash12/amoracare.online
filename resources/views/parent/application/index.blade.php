@extends('layouts.dashboard', ['title' => 'My Application'])

@section('content')
@php
    $safeProgress = max(0, min(100, (int) ($documentProgressPercent ?? 0)));

    $caseStatus = $adoptionCase?->status ?? 'no_case';
    $caseStatusLabel = $adoptionCase?->status_label ?? 'No active case';

    $statusTheme = match ($caseStatus) {
        'draft', 'application_started' => [
            'class' => 'is-neutral',
            'icon' => 'bi-pencil-square',
            'message' => 'Your application has been started and may still require information or documents.',
        ],
        'document_collection', 'document_review' => [
            'class' => 'is-blue',
            'icon' => 'bi-folder-check',
            'message' => 'Your application is currently in the document preparation and review stage.',
        ],
        'assessment', 'home_study', 'racco_review' => [
            'class' => 'is-purple',
            'icon' => 'bi-clipboard2-pulse',
            'message' => 'Authorized staff are assessing your application and submitted requirements.',
        ],
        'matching_review', 'matching' => [
            'class' => 'is-amber',
            'icon' => 'bi-diagram-3',
            'message' => 'Your case has reached a controlled matching or placement-review stage.',
        ],
        'placement', 'supervision' => [
            'class' => 'is-teal',
            'icon' => 'bi-house-heart',
            'message' => 'Your case is in a placement or post-placement supervision stage.',
        ],
        'finalized', 'closed' => [
            'class' => 'is-green',
            'icon' => 'bi-patch-check',
            'message' => 'This case has reached a completed or closed workflow stage.',
        ],
        'cancelled', 'rejected' => [
            'class' => 'is-red',
            'icon' => 'bi-exclamation-octagon',
            'message' => 'This case requires clarification from authorized AmoraCare staff.',
        ],
        default => [
            'class' => 'is-neutral',
            'icon' => 'bi-hourglass-split',
            'message' => $adoptionCase
                ? 'Your application is being processed according to the current adoption workflow.'
                : 'Your account is active, but an adoption case has not yet been assigned.',
        ],
    };

    $documentStatusMeta = [
        'pending' => [
            'label' => 'Not submitted',
            'class' => 'is-pending',
            'icon' => 'bi-clock',
        ],
        'submitted' => [
            'label' => 'Submitted',
            'class' => 'is-submitted',
            'icon' => 'bi-cloud-check',
        ],
        'under_review' => [
            'label' => 'Under review',
            'class' => 'is-review',
            'icon' => 'bi-search',
        ],
        'verified' => [
            'label' => 'Verified',
            'class' => 'is-verified',
            'icon' => 'bi-patch-check',
        ],
        'rejected' => [
            'label' => 'Needs revision',
            'class' => 'is-rejected',
            'icon' => 'bi-exclamation-triangle',
        ],
        'expired' => [
            'label' => 'Expired',
            'class' => 'is-expired',
            'icon' => 'bi-calendar-x',
        ],
    ];

    $needsActionStatuses = ['pending', 'rejected', 'expired'];

    $needsActionDocuments = $parentDocuments
        ->whereIn('status', $needsActionStatuses)
        ->values();

    $waitingDocumentsCount = $parentDocuments
        ->whereIn('status', ['submitted', 'under_review'])
        ->count();

    $latestUpdate = $parentUpdates->first();

    $currentTimelineStep = $applicationTimeline
        ->first(fn ($step) => ($step['state'] ?? null) === 'current');

    $completedTimelineCount = $applicationTimeline
        ->filter(fn ($step) => ($step['state'] ?? null) === 'completed')
        ->count();

    $timelineCount = max(1, $applicationTimeline->count());

    $timelineProgress = $applicationTimeline->count() > 0
        ? (int) round((($completedTimelineCount + ($currentTimelineStep ? 0.5 : 0)) / $timelineCount) * 100)
        : 0;

    $financialScore = max(0, min(100, (int) ($parentMatchingProfile?->financial_capacity_score ?? 0)));
    $housingScore = max(0, min(100, (int) ($parentMatchingProfile?->housing_score ?? 0)));
    $parentingScore = max(0, min(100, (int) ($parentMatchingProfile?->parenting_capacity_score ?? 0)));
@endphp

<style>
    .application-page {
        --app-primary: #9f3f24;
        --app-primary-dark: #7c2d19;
        --app-primary-soft: #fff3ed;
        --app-blue: #2563eb;
        --app-purple: #7c3aed;
        --app-green: #15803d;
        --app-amber: #b45309;
        --app-red: #b91c1c;
        --app-teal: #0f766e;
        --app-ink: #172033;
        --app-muted: #667085;
        --app-line: #e5e9f0;
        --app-soft: #f7f9fc;
        --app-surface: #ffffff;
        display: grid;
        gap: 18px;
        color: var(--app-ink);
    }

    .application-page *,
    .application-page *::before,
    .application-page *::after {
        box-sizing: border-box;
    }

    .app-privacy {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        padding: 15px 17px;
        border: 1px solid #bfdbfe;
        border-radius: 17px;
        background: #eff6ff;
        color: #1e40af;
        line-height: 1.6;
    }

    .app-privacy-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 18px;
    }

    .app-privacy strong {
        display: block;
        margin-bottom: 2px;
        color: #1e3a8a;
    }

    .app-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
        padding: 27px;
        border: 1px solid #f0d7cd;
        border-radius: 24px;
        background:
            radial-gradient(circle at 90% 8%, rgba(159, 63, 36, 0.12), transparent 31%),
            linear-gradient(135deg, #fffaf7 0%, #ffffff 60%, #f8fafc 100%);
        box-shadow: 0 18px 42px rgba(20, 31, 51, 0.07);
    }

    .app-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        padding: 7px 11px;
        border: 1px solid #f1c8b8;
        border-radius: 999px;
        background: var(--app-primary-soft);
        color: var(--app-primary-dark);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .app-hero h1 {
        margin: 0;
        color: #121827;
        font-size: clamp(27px, 3.2vw, 39px);
        line-height: 1.15;
    }

    .app-hero-description {
        max-width: 740px;
        margin: 10px 0 0;
        color: var(--app-muted);
        font-size: 15px;
        line-height: 1.7;
    }

    .app-hero-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .app-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid var(--app-line);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        color: #475467;
        font-size: 13px;
        font-weight: 850;
    }

    .app-status-card {
        width: min(100%, 290px);
        padding: 18px;
        border: 1px solid var(--app-line);
        border-radius: 19px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 14px 30px rgba(20, 31, 51, 0.08);
    }

    .app-status-top {
        display: flex;
        gap: 11px;
        align-items: center;
    }

    .app-status-icon {
        width: 47px;
        height: 47px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 15px;
        background: #f2f4f7;
        color: #475467;
        font-size: 21px;
    }

    .app-status-label {
        color: var(--app-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .app-status-value {
        margin-top: 3px;
        color: #101828;
        font-size: 17px;
        font-weight: 950;
        line-height: 1.3;
    }

    .app-status-message {
        margin: 12px 0 0;
        color: var(--app-muted);
        font-size: 12px;
        line-height: 1.55;
    }

    .app-status-card.is-blue .app-status-icon {
        background: #dbeafe;
        color: var(--app-blue);
    }

    .app-status-card.is-purple .app-status-icon {
        background: #ede9fe;
        color: var(--app-purple);
    }

    .app-status-card.is-amber .app-status-icon {
        background: #fef3c7;
        color: var(--app-amber);
    }

    .app-status-card.is-teal .app-status-icon {
        background: #ccfbf1;
        color: var(--app-teal);
    }

    .app-status-card.is-green .app-status-icon {
        background: #dcfce7;
        color: var(--app-green);
    }

    .app-status-card.is-red .app-status-icon {
        background: #fee2e2;
        color: var(--app-red);
    }

    .app-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .app-btn {
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

    .app-btn:hover {
        border-color: #b7bec9;
        background: #f9fafb;
        color: #101828;
    }

    .app-btn.is-primary {
        border-color: var(--app-primary);
        background: var(--app-primary);
        color: #ffffff;
    }

    .app-btn.is-primary:hover {
        border-color: var(--app-primary-dark);
        background: var(--app-primary-dark);
        color: #ffffff;
    }

    .app-btn:focus-visible,
    .app-tab:focus-visible,
    .app-disclosure summary:focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.24);
        outline-offset: 2px;
    }

    .app-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .app-stat {
        min-width: 0;
        padding: 17px;
        border: 1px solid var(--app-line);
        border-radius: 18px;
        background: var(--app-surface);
        box-shadow: 0 10px 24px rgba(20, 31, 51, 0.045);
    }

    .app-stat-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
    }

    .app-stat-label {
        color: var(--app-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .app-stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #f1f5f9;
        color: var(--app-blue);
        font-size: 18px;
    }

    .app-stat-value {
        margin-top: 12px;
        color: #101828;
        font-size: 28px;
        font-weight: 950;
        line-height: 1;
    }

    .app-stat-value.is-text {
        font-size: 17px;
        line-height: 1.3;
    }

    .app-stat-help {
        margin-top: 6px;
        color: var(--app-muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .app-stat.is-action .app-stat-icon {
        background: #fff1f2;
        color: var(--app-red);
    }

    .app-stat.is-review .app-stat-icon {
        background: #eff6ff;
        color: var(--app-blue);
    }

    .app-stat.is-verified .app-stat-icon {
        background: #ecfdf3;
        color: var(--app-green);
    }

    .app-next-step {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 17px 19px;
        border: 1px solid #fed7aa;
        border-radius: 18px;
        background: #fff8ed;
    }

    .app-next-step.is-clear {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .app-next-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 14px;
        background: #ffedd5;
        color: #c2410c;
        font-size: 19px;
    }

    .app-next-step.is-clear .app-next-icon {
        background: #dcfce7;
        color: #15803d;
    }

    .app-next-copy strong {
        display: block;
        color: #8a3e08;
    }

    .app-next-step.is-clear .app-next-copy strong {
        color: #166534;
    }

    .app-next-copy p {
        margin: 4px 0 0;
        color: #9a4f0c;
        font-size: 13px;
        line-height: 1.55;
    }

    .app-next-step.is-clear .app-next-copy p {
        color: #166534;
    }

    .app-workspace {
        overflow: hidden;
        border: 1px solid var(--app-line);
        border-radius: 22px;
        background: var(--app-surface);
        box-shadow: 0 14px 34px rgba(20, 31, 51, 0.055);
    }

    .app-tabs-wrap {
        padding: 10px;
        border-bottom: 1px solid var(--app-line);
        background: #fbfcfe;
        overflow-x: auto;
    }

    .app-tabs {
        display: flex;
        gap: 7px;
        min-width: max-content;
    }

    .app-tab {
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

    .app-tab:hover {
        background: #f2f4f7;
        color: #344054;
    }

    .app-tab.is-active {
        border-color: #f1c8b8;
        background: var(--app-primary-soft);
        color: var(--app-primary-dark);
    }

    .app-tab-count {
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

    .app-tab.is-active .app-tab-count {
        background: #f4cdbc;
        color: var(--app-primary-dark);
    }

    .app-panel {
        display: none;
        padding: 22px;
    }

    .app-panel.is-active {
        display: block;
    }

    .app-panel-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .app-panel-header h2 {
        margin: 0;
        color: #101828;
        font-size: 21px;
    }

    .app-panel-header p {
        margin: 6px 0 0;
        color: var(--app-muted);
        line-height: 1.55;
    }

    .app-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border: 1px solid var(--app-line);
        border-radius: 999px;
        background: var(--app-soft);
        color: #475467;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    .app-overview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .app-info-card {
        border: 1px solid var(--app-line);
        border-radius: 17px;
        background: #ffffff;
        overflow: hidden;
    }

    .app-info-card-header {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 15px 16px;
        border-bottom: 1px solid var(--app-line);
        background: #fbfcfe;
    }

    .app-info-card-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .app-info-card-header strong {
        color: #101828;
    }

    .app-info-list {
        display: grid;
    }

    .app-info-row {
        display: grid;
        grid-template-columns: minmax(140px, 0.8fr) minmax(0, 1.2fr);
        gap: 14px;
        padding: 13px 16px;
        border-bottom: 1px solid #f0f2f5;
    }

    .app-info-row:last-child {
        border-bottom: none;
    }

    .app-info-label {
        color: var(--app-muted);
        font-size: 12px;
        font-weight: 800;
    }

    .app-info-value {
        color: #344054;
        font-size: 13px;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .app-latest-update {
        margin-top: 14px;
        padding: 16px;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        background: #eff6ff;
    }

    .app-latest-update-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .app-latest-update strong {
        color: #1e3a8a;
    }

    .app-latest-update time {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
    }

    .app-latest-update p {
        margin: 8px 0 0;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.65;
    }

    .app-timeline-progress {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid var(--app-line);
        border-radius: 16px;
        background: var(--app-soft);
    }

    .app-progress-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 9px;
    }

    .app-progress-top span {
        color: #475467;
        font-size: 12px;
        font-weight: 850;
    }

    .app-progress-top strong {
        color: #101828;
        font-size: 20px;
    }

    .app-progress-track {
        height: 10px;
        overflow: hidden;
        border-radius: 999px;
        background: #e4e7ec;
    }

    .app-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--app-primary), #e58b67);
    }

    .app-timeline {
        position: relative;
        display: grid;
        gap: 0;
    }

    .app-timeline::before {
        content: "";
        position: absolute;
        top: 22px;
        bottom: 22px;
        left: 20px;
        width: 2px;
        background: #e4e7ec;
    }

    .app-timeline-step {
        position: relative;
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        gap: 13px;
        align-items: center;
        min-height: 68px;
        padding: 8px 0;
    }

    .app-timeline-marker {
        position: relative;
        z-index: 1;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border: 3px solid #ffffff;
        border-radius: 50%;
        background: #e4e7ec;
        color: #667085;
        box-shadow: 0 0 0 1px #d0d5dd;
    }

    .app-timeline-step.is-completed .app-timeline-marker {
        background: #dcfce7;
        color: #15803d;
        box-shadow: 0 0 0 1px #86efac;
    }

    .app-timeline-step.is-current .app-timeline-marker {
        background: var(--app-primary);
        color: #ffffff;
        box-shadow:
            0 0 0 1px var(--app-primary),
            0 0 0 5px rgba(159, 63, 36, 0.12);
    }

    .app-timeline-copy strong {
        display: block;
        color: #344054;
        font-size: 14px;
    }

    .app-timeline-copy span {
        display: block;
        margin-top: 3px;
        color: var(--app-muted);
        font-size: 12px;
    }

    .app-state-badge {
        display: inline-flex;
        padding: 6px 9px;
        border-radius: 999px;
        background: #f2f4f7;
        color: #667085;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .app-timeline-step.is-completed .app-state-badge {
        background: #dcfce7;
        color: #166534;
    }

    .app-timeline-step.is-current .app-state-badge {
        background: var(--app-primary-soft);
        color: var(--app-primary-dark);
    }

    .app-doc-summary {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: center;
        margin-bottom: 16px;
        padding: 16px;
        border: 1px solid var(--app-line);
        border-radius: 16px;
        background: var(--app-soft);
    }

    .app-doc-summary p {
        margin: 5px 0 0;
        color: var(--app-muted);
        font-size: 13px;
        line-height: 1.55;
    }

    .app-doc-progress {
        min-width: 92px;
        text-align: right;
    }

    .app-doc-progress strong {
        display: block;
        color: #101828;
        font-size: 27px;
    }

    .app-doc-progress span {
        color: var(--app-muted);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .app-document-list {
        display: grid;
        gap: 9px;
    }

    .app-document {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 13px 14px;
        border: 1px solid var(--app-line);
        border-radius: 14px;
        background: #ffffff;
    }

    .app-document-icon {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #f2f4f7;
        color: #667085;
    }

    .app-document.is-pending .app-document-icon {
        background: #fef3c7;
        color: #92400e;
    }

    .app-document.is-submitted .app-document-icon,
    .app-document.is-review .app-document-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .app-document.is-verified .app-document-icon {
        background: #dcfce7;
        color: #166534;
    }

    .app-document.is-rejected .app-document-icon,
    .app-document.is-expired .app-document-icon {
        background: #fee2e2;
        color: #991b1b;
    }

    .app-document-copy {
        min-width: 0;
    }

    .app-document-copy strong {
        display: block;
        color: #344054;
        font-size: 13px;
    }

    .app-document-copy span,
    .app-document-copy p {
        display: block;
        margin: 3px 0 0;
        color: var(--app-muted);
        font-size: 11px;
        line-height: 1.5;
    }

    .app-document-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 8px;
        border-radius: 999px;
        background: #f2f4f7;
        color: #667085;
        font-size: 10px;
        font-weight: 900;
        white-space: nowrap;
    }

    .app-document-status.is-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .app-document-status.is-submitted {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .app-document-status.is-review {
        background: #ede9fe;
        color: #6d28d9;
    }

    .app-document-status.is-verified {
        background: #dcfce7;
        color: #166534;
    }

    .app-document-status.is-rejected,
    .app-document-status.is-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    .app-updates {
        display: grid;
        gap: 11px;
    }

    .app-update {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--app-line);
        border-radius: 15px;
        background: #ffffff;
    }

    .app-update-icon {
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .app-update-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .app-update-header strong {
        color: #344054;
    }

    .app-update-header time {
        color: var(--app-muted);
        font-size: 11px;
        font-weight: 800;
    }

    .app-update p {
        margin: 7px 0 0;
        color: #475467;
        font-size: 13px;
        line-height: 1.65;
        white-space: pre-line;
    }

    .app-profile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .app-disclosure {
        overflow: hidden;
        border: 1px solid var(--app-line);
        border-radius: 16px;
        background: #ffffff;
    }

    .app-disclosure summary {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 15px 16px;
        list-style: none;
        cursor: pointer;
        user-select: none;
    }

    .app-disclosure summary::-webkit-details-marker {
        display: none;
    }

    .app-disclosure-title {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .app-disclosure-title i {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #f2f4f7;
        color: #475467;
    }

    .app-disclosure-title strong {
        display: block;
        color: #101828;
        font-size: 14px;
    }

    .app-disclosure-title span {
        display: block;
        margin-top: 2px;
        color: var(--app-muted);
        font-size: 11px;
    }

    .app-disclosure-chevron {
        color: #667085;
        transition: transform 0.2s ease;
    }

    .app-disclosure[open] .app-disclosure-chevron {
        transform: rotate(180deg);
    }

    .app-disclosure-content {
        border-top: 1px solid var(--app-line);
    }

    .app-score-list {
        display: grid;
        gap: 13px;
        padding: 15px 16px;
        border-top: 1px solid var(--app-line);
    }

    .app-score-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 6px;
        color: #475467;
        font-size: 12px;
        font-weight: 800;
    }

    .app-score-top strong {
        color: #101828;
    }

    .app-score-track {
        height: 8px;
        overflow: hidden;
        border-radius: 999px;
        background: #e4e7ec;
    }

    .app-score-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--app-primary), #e58b67);
    }

    .app-empty {
        padding: 34px 20px;
        text-align: center;
        color: var(--app-muted);
    }

    .app-empty-icon {
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

    .app-empty h3 {
        margin: 0;
        color: #101828;
    }

    .app-empty p {
        max-width: 620px;
        margin: 7px auto 0;
        line-height: 1.65;
    }

    @media (max-width: 1080px) {
        .app-stats,
        .app-overview-grid,
        .app-profile-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .application-page {
            gap: 14px;
        }

        .app-hero {
            grid-template-columns: 1fr;
            padding: 21px;
        }

        .app-status-card {
            width: 100%;
        }

        .app-stats,
        .app-overview-grid,
        .app-profile-grid {
            grid-template-columns: 1fr;
        }

        .app-next-step {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .app-next-step .app-btn {
            grid-column: 1 / -1;
        }

        .app-panel {
            padding: 17px;
        }

        .app-info-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .app-document {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .app-document-status {
            grid-column: 2;
            justify-self: start;
        }

        .app-doc-summary {
            grid-template-columns: 1fr;
        }

        .app-doc-progress {
            text-align: left;
        }
    }

    @media (max-width: 480px) {
        .app-actions,
        .app-actions .app-btn {
            width: 100%;
        }

        .app-timeline-step {
            grid-template-columns: 42px minmax(0, 1fr);
        }

        .app-state-badge {
            grid-column: 2;
            justify-self: start;
        }

        .app-update {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .application-page *,
        .application-page *::before,
        .application-page *::after {
            scroll-behavior: auto !important;
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<div class="application-page">
    <section class="app-privacy" aria-label="Application privacy notice">
        <div class="app-privacy-icon">
            <i class="bi bi-shield-lock"></i>
        </div>

        <div>
            <strong>Your applicant view is private and limited.</strong>
            This page shows only your own application, parent-side requirements, and updates that
            authorized AmoraCare staff marked as visible to you. Child profiles, matching rankings,
            confidential notes, and other applicants’ records are not shown.
        </div>
    </section>

    <section class="app-hero">
        <div>
            <div class="app-eyebrow">
                <i class="bi bi-file-earmark-person"></i>
                My application
            </div>

            <h1>Welcome, {{ $user->name }}</h1>

            <p class="app-hero-description">
                Follow your case progress, review official updates, and see exactly which documents
                or actions need your attention.
            </p>

            <div class="app-hero-meta">
                <span class="app-meta-pill">
                    <i class="bi bi-person-check"></i>
                    {{ $user->role?->name ?? 'Prospective Parent' }}
                </span>

                @if($adoptionCase)
                    <span class="app-meta-pill">
                        <i class="bi bi-folder2-open"></i>
                        Case {{ $adoptionCase->case_code }}
                    </span>

                    <span class="app-meta-pill">
                        <i class="bi bi-calendar3"></i>
                        Opened {{ $adoptionCase->opened_at?->format('M d, Y') ?? 'date not set' }}
                    </span>
                @endif
            </div>

            <div class="app-actions">
                <a href="{{ route('parent.documents.index') }}" class="app-btn is-primary">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    Manage documents
                </a>

                <a href="{{ route('parent.ai.index') }}" class="app-btn">
                    <i class="bi bi-chat-square-text"></i>
                    Ask AmoraCare Guide
                </a>
            </div>
        </div>

        <aside class="app-status-card {{ $statusTheme['class'] }}">
            <div class="app-status-top">
                <div class="app-status-icon">
                    <i class="bi {{ $statusTheme['icon'] }}"></i>
                </div>

                <div>
                    <div class="app-status-label">Current application status</div>
                    <div class="app-status-value">{{ $caseStatusLabel }}</div>
                </div>
            </div>

            <p class="app-status-message">
                {{ $statusTheme['message'] }}
            </p>
        </aside>
    </section>

    @if($adoptionCase)
        <section class="app-stats" aria-label="Application overview">
            <article class="app-stat">
                <div class="app-stat-top">
                    <div class="app-stat-label">Current stage</div>
                    <div class="app-stat-icon">
                        <i class="bi bi-signpost-split"></i>
                    </div>
                </div>

                <div class="app-stat-value is-text">
                    {{ $currentTimelineStep['label'] ?? $caseStatusLabel }}
                </div>

                <div class="app-stat-help">
                    Based on your current workflow status
                </div>
            </article>

            <article class="app-stat is-action">
                <div class="app-stat-top">
                    <div class="app-stat-label">Needs action</div>
                    <div class="app-stat-icon">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                </div>

                <div class="app-stat-value">{{ $needsActionDocuments->count() }}</div>

                <div class="app-stat-help">
                    Pending, rejected, or expired documents
                </div>
            </article>

            <article class="app-stat is-review">
                <div class="app-stat-top">
                    <div class="app-stat-label">In review</div>
                    <div class="app-stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>

                <div class="app-stat-value">{{ $waitingDocumentsCount }}</div>

                <div class="app-stat-help">
                    Submitted files waiting for staff action
                </div>
            </article>

            <article class="app-stat is-verified">
                <div class="app-stat-top">
                    <div class="app-stat-label">Verified</div>
                    <div class="app-stat-icon">
                        <i class="bi bi-patch-check"></i>
                    </div>
                </div>

                <div class="app-stat-value">{{ $safeProgress }}%</div>

                <div class="app-stat-help">
                    {{ $verifiedDocumentsCount }} of {{ $requiredDocumentsCount }} requirements accepted
                </div>
            </article>
        </section>

        @if($needsActionDocuments->count() > 0)
            <section class="app-next-step">
                <div class="app-next-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>

                <div class="app-next-copy">
                    <strong>
                        {{ $needsActionDocuments->count() }}
                        {{ Str::plural('document', $needsActionDocuments->count()) }}
                        need{{ $needsActionDocuments->count() === 1 ? 's' : '' }} attention
                    </strong>

                    <p>
                        Review pending, rejected, or expired requirements and upload the correct files
                        from your document center.
                    </p>
                </div>

                <a href="{{ route('parent.documents.index') }}" class="app-btn is-primary">
                    Review documents
                    <i class="bi bi-arrow-right"></i>
                </a>
            </section>
        @else
            <section class="app-next-step is-clear">
                <div class="app-next-icon">
                    <i class="bi bi-check2-circle"></i>
                </div>

                <div class="app-next-copy">
                    <strong>No immediate document action is required</strong>
                    <p>
                        Continue monitoring official updates while authorized staff review your application.
                    </p>
                </div>

                <button
                    type="button"
                    class="app-btn"
                    data-app-tab-open="updates"
                >
                    View updates
                    <i class="bi bi-arrow-right"></i>
                </button>
            </section>
        @endif

        <section class="app-workspace">
            <div class="app-tabs-wrap">
                <div class="app-tabs" role="tablist" aria-label="Application sections">
                    <button
                        type="button"
                        class="app-tab is-active"
                        id="appTabOverview"
                        role="tab"
                        aria-selected="true"
                        aria-controls="appPanelOverview"
                        data-app-tab="overview"
                    >
                        <i class="bi bi-grid"></i>
                        Overview
                    </button>

                    <button
                        type="button"
                        class="app-tab"
                        id="appTabTimeline"
                        role="tab"
                        aria-selected="false"
                        aria-controls="appPanelTimeline"
                        data-app-tab="timeline"
                    >
                        <i class="bi bi-signpost-split"></i>
                        Timeline
                        <span class="app-tab-count">{{ $applicationTimeline->count() }}</span>
                    </button>

                    <button
                        type="button"
                        class="app-tab"
                        id="appTabDocuments"
                        role="tab"
                        aria-selected="false"
                        aria-controls="appPanelDocuments"
                        data-app-tab="documents"
                    >
                        <i class="bi bi-files"></i>
                        Documents
                        <span class="app-tab-count">{{ $parentDocuments->count() }}</span>
                    </button>

                    <button
                        type="button"
                        class="app-tab"
                        id="appTabUpdates"
                        role="tab"
                        aria-selected="false"
                        aria-controls="appPanelUpdates"
                        data-app-tab="updates"
                    >
                        <i class="bi bi-bell"></i>
                        Updates
                        <span class="app-tab-count">{{ $parentUpdates->count() }}</span>
                    </button>

                    <button
                        type="button"
                        class="app-tab"
                        id="appTabProfile"
                        role="tab"
                        aria-selected="false"
                        aria-controls="appPanelProfile"
                        data-app-tab="profile"
                    >
                        <i class="bi bi-person-lines-fill"></i>
                        My profile
                    </button>
                </div>
            </div>

            <div
                class="app-panel is-active"
                id="appPanelOverview"
                role="tabpanel"
                aria-labelledby="appTabOverview"
                data-app-panel="overview"
            >
                <div class="app-panel-header">
                    <div>
                        <h2>Application overview</h2>
                        <p>Your key case information and most recent parent-visible update.</p>
                    </div>

                    <span class="app-badge">
                        <i class="bi bi-folder-check"></i>
                        {{ $adoptionCase->case_code }}
                    </span>
                </div>

                <div class="app-overview-grid">
                    <article class="app-info-card">
                        <header class="app-info-card-header">
                            <div class="app-info-card-icon">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <strong>Case information</strong>
                        </header>

                        <div class="app-info-list">
                            <div class="app-info-row">
                                <span class="app-info-label">Case type</span>
                                <span class="app-info-value">{{ $adoptionCase->case_type_label }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Current status</span>
                                <span class="app-info-value">{{ $adoptionCase->status_label }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Priority</span>
                                <span class="app-info-value">{{ $adoptionCase->priority_label }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Opened date</span>
                                <span class="app-info-value">
                                    {{ $adoptionCase->opened_at?->format('M d, Y') ?? 'Not set' }}
                                </span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Target completion</span>
                                <span class="app-info-value">
                                    {{ $adoptionCase->target_completion_date?->format('M d, Y') ?? 'Not set' }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <article class="app-info-card">
                        <header class="app-info-card-header">
                            <div class="app-info-card-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <strong>Assigned support</strong>
                        </header>

                        <div class="app-info-list">
                            <div class="app-info-row">
                                <span class="app-info-label">Assigned staff / social worker</span>
                                <span class="app-info-value">
                                    {{ $adoptionCase->assignedSocialWorker?->name ?? 'Not assigned' }}
                                </span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Applicant</span>
                                <span class="app-info-value">{{ $user->name }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Email</span>
                                <span class="app-info-value">{{ $user->email }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Account status</span>
                                <span class="app-info-value">{{ ucfirst($user->status ?? 'N/A') }}</span>
                            </div>

                            <div class="app-info-row">
                                <span class="app-info-label">Home study</span>
                                <span class="app-info-value">
                                    {{ $parentMatchingProfile?->home_study_verified ? 'Verified' : 'Not yet verified' }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                @if($latestUpdate)
                    <article class="app-latest-update">
                        <div class="app-latest-update-top">
                            <strong>
                                <i class="bi bi-megaphone"></i>
                                {{ $latestUpdate->title ?? $latestUpdate->note_type_label ?? 'Latest update' }}
                            </strong>

                            <time datetime="{{ $latestUpdate->created_at?->toIso8601String() }}">
                                {{ $latestUpdate->created_at?->format('M d, Y h:i A') }}
                            </time>
                        </div>

                        <p>{{ $latestUpdate->body }}</p>
                    </article>
                @endif
            </div>

            <div
                class="app-panel"
                id="appPanelTimeline"
                role="tabpanel"
                aria-labelledby="appTabTimeline"
                data-app-panel="timeline"
                hidden
            >
                <div class="app-panel-header">
                    <div>
                        <h2>Application timeline</h2>
                        <p>
                            A simplified view of completed, current, and upcoming workflow stages.
                        </p>
                    </div>

                    <span class="app-badge">
                        <i class="bi bi-signpost"></i>
                        {{ $currentTimelineStep['label'] ?? 'Current stage unavailable' }}
                    </span>
                </div>

                <div class="app-timeline-progress">
                    <div class="app-progress-top">
                        <span>Estimated workflow position</span>
                        <strong>{{ $timelineProgress }}%</strong>
                    </div>

                    <div
                        class="app-progress-track"
                        role="progressbar"
                        aria-valuenow="{{ $timelineProgress }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Application timeline progress"
                    >
                        <div
                            class="app-progress-fill"
                            style="width: {{ $timelineProgress }}%;"
                        ></div>
                    </div>
                </div>

                <div class="app-timeline">
                    @forelse($applicationTimeline as $step)
                        @php
                            $stepState = $step['state'] ?? 'upcoming';

                            $stepIcon = match ($stepState) {
                                'completed' => 'bi-check-lg',
                                'current' => 'bi-record-circle',
                                default => 'bi-circle',
                            };

                            $stepDescription = match ($stepState) {
                                'completed' => 'Completed workflow stage',
                                'current' => 'Your application is currently at this stage',
                                default => 'Upcoming workflow stage',
                            };
                        @endphp

                        <article class="app-timeline-step is-{{ $stepState }}">
                            <div class="app-timeline-marker">
                                <i class="bi {{ $stepIcon }}"></i>
                            </div>

                            <div class="app-timeline-copy">
                                <strong>{{ $step['label'] }}</strong>
                                <span>{{ $stepDescription }}</span>
                            </div>

                            <span class="app-state-badge">
                                {{ ucfirst($stepState) }}
                            </span>
                        </article>
                    @empty
                        <div class="app-empty">
                            <div class="app-empty-icon">
                                <i class="bi bi-signpost-split"></i>
                            </div>
                            <h3>No timeline is available</h3>
                            <p>The current case status could not be mapped to the application workflow.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div
                class="app-panel"
                id="appPanelDocuments"
                role="tabpanel"
                aria-labelledby="appTabDocuments"
                data-app-panel="documents"
                hidden
            >
                <div class="app-panel-header">
                    <div>
                        <h2>Required documents</h2>
                        <p>
                            A summary of your parent-side checklist. Uploads and replacements are managed
                            in the document center.
                        </p>
                    </div>

                    <a href="{{ route('parent.documents.index') }}" class="app-btn is-primary">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                        Open document center
                    </a>
                </div>

                <div class="app-doc-summary">
                    <div>
                        <strong>
                            {{ $verifiedDocumentsCount }} of {{ $requiredDocumentsCount }} verified
                        </strong>
                        <p>
                            Submitted: {{ $submittedDocumentsCount }} ·
                            Waiting for review: {{ $waitingDocumentsCount }} ·
                            Needs action: {{ $needsActionDocuments->count() }}
                        </p>

                        <div class="app-progress-track" style="margin-top: 11px;">
                            <div
                                class="app-progress-fill"
                                style="width: {{ $safeProgress }}%;"
                            ></div>
                        </div>
                    </div>

                    <div class="app-doc-progress">
                        <strong>{{ $safeProgress }}%</strong>
                        <span>Verified</span>
                    </div>
                </div>

                @if($parentDocuments->count())
                    <div class="app-document-list">
                        @foreach($parentDocuments as $document)
                            @php
                                $meta = $documentStatusMeta[$document->status] ?? [
                                    'label' => $document->status_label ?? ucfirst((string) $document->status),
                                    'class' => 'is-pending',
                                    'icon' => 'bi-info-circle',
                                ];
                            @endphp

                            <article class="app-document {{ $meta['class'] }}">
                                <div class="app-document-icon">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                </div>

                                <div class="app-document-copy">
                                    <strong>{{ $document->document_name }}</strong>
                                    <span>{{ $document->scope_label ?? 'Parent requirement' }}</span>

                                    @if($document->remarks)
                                        <p>{{ $document->remarks }}</p>
                                    @endif
                                </div>

                                <span class="app-document-status {{ $meta['class'] }}">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                    {{ $meta['label'] }}
                                </span>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="app-empty">
                        <div class="app-empty-icon">
                            <i class="bi bi-file-earmark"></i>
                        </div>
                        <h3>No checklist assigned</h3>
                        <p>Authorized AmoraCare staff have not assigned parent-side requirements yet.</p>
                    </div>
                @endif
            </div>

            <div
                class="app-panel"
                id="appPanelUpdates"
                role="tabpanel"
                aria-labelledby="appTabUpdates"
                data-app-panel="updates"
                hidden
            >
                <div class="app-panel-header">
                    <div>
                        <h2>Official case updates</h2>
                        <p>
                            Only notes marked as parent-visible by authorized staff appear here.
                        </p>
                    </div>

                    <span class="app-badge">
                        <i class="bi bi-bell"></i>
                        {{ $parentUpdates->count() }}
                        {{ Str::plural('update', $parentUpdates->count()) }}
                    </span>
                </div>

                @if($parentUpdates->count())
                    <div class="app-updates">
                        @foreach($parentUpdates as $update)
                            <article class="app-update">
                                <div class="app-update-icon">
                                    <i class="bi bi-megaphone"></i>
                                </div>

                                <div>
                                    <div class="app-update-header">
                                        <strong>
                                            {{ $update->title ?? $update->note_type_label ?? 'Case update' }}
                                        </strong>

                                        <time datetime="{{ $update->created_at?->toIso8601String() }}">
                                            {{ $update->created_at?->format('M d, Y h:i A') }}
                                        </time>
                                    </div>

                                    <p>{{ $update->body }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="app-empty">
                        <div class="app-empty-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3>No parent-visible updates yet</h3>
                        <p>New official updates will appear here when authorized staff publish them.</p>
                    </div>
                @endif
            </div>

            <div
                class="app-panel"
                id="appPanelProfile"
                role="tabpanel"
                aria-labelledby="appTabProfile"
                data-app-panel="profile"
                hidden
            >
                <div class="app-panel-header">
                    <div>
                        <h2>My applicant profile</h2>
                        <p>
                            Review the personal and matching information currently stored on your account.
                        </p>
                    </div>

                    <span class="app-badge">
                        <i class="bi bi-person-check"></i>
                        {{ ucfirst($user->status ?? 'N/A') }}
                    </span>
                </div>

                <div class="app-profile-grid">
                    <details class="app-disclosure" open>
                        <summary>
                            <div class="app-disclosure-title">
                                <i class="bi bi-person-vcard"></i>
                                <div>
                                    <strong>Personal account details</strong>
                                    <span>Name, contact information, and account activity</span>
                                </div>
                            </div>

                            <i class="bi bi-chevron-down app-disclosure-chevron"></i>
                        </summary>

                        <div class="app-disclosure-content">
                            <div class="app-info-list">
                                <div class="app-info-row">
                                    <span class="app-info-label">Full name</span>
                                    <span class="app-info-value">{{ $user->name }}</span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Email address</span>
                                    <span class="app-info-value">{{ $user->email }}</span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Phone number</span>
                                    <span class="app-info-value">
                                        {{ $user->phone_number ?? 'Not provided' }}
                                    </span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Email verification</span>
                                    <span class="app-info-value">
                                        {{ $user->email_verified_at ? 'Verified' : 'Not verified' }}
                                    </span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Last login</span>
                                    <span class="app-info-value">
                                        {{ $user->last_login_at?->format('M d, Y h:i A') ?? 'No login recorded' }}
                                    </span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Account created</span>
                                    <span class="app-info-value">
                                        {{ $user->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="app-info-row">
                                    <span class="app-info-label">Last updated</span>
                                    <span class="app-info-value">
                                        {{ $user->updated_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </details>

                    <details class="app-disclosure" open>
                        <summary>
                            <div class="app-disclosure-title">
                                <i class="bi bi-sliders"></i>
                                <div>
                                    <strong>Matching profile</strong>
                                    <span>Preferences and readiness information used by staff</span>
                                </div>
                            </div>

                            <i class="bi bi-chevron-down app-disclosure-chevron"></i>
                        </summary>

                        @if($parentMatchingProfile)
                            <div class="app-disclosure-content">
                                <div class="app-info-list">
                                    <div class="app-info-row">
                                        <span class="app-info-label">Preferred child sex</span>
                                        <span class="app-info-value">
                                            {{ ucfirst($parentMatchingProfile->preferred_child_sex ?? 'Any') }}
                                        </span>
                                    </div>

                                    <div class="app-info-row">
                                        <span class="app-info-label">Preferred age range</span>
                                        <span class="app-info-value">
                                            {{ $parentMatchingProfile->min_child_age ?? 'No minimum' }}
                                            –
                                            {{ $parentMatchingProfile->max_child_age ?? 'No maximum' }}
                                            years old
                                        </span>
                                    </div>

                                    <div class="app-info-row">
                                        <span class="app-info-label">Open to special needs</span>
                                        <span class="app-info-value">
                                            {{ $parentMatchingProfile->open_to_special_needs ? 'Yes' : 'No' }}
                                        </span>
                                    </div>

                                    <div class="app-info-row">
                                        <span class="app-info-label">Home study status</span>
                                        <span class="app-info-value">
                                            {{ $parentMatchingProfile->home_study_verified ? 'Verified' : 'Not verified' }}
                                        </span>
                                    </div>

                                    <div class="app-info-row">
                                        <span class="app-info-label">Matching notes</span>
                                        <span class="app-info-value">
                                            {{ $parentMatchingProfile->matching_notes ?? 'No matching notes recorded' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="app-score-list">
                                    <div>
                                        <div class="app-score-top">
                                            <span>Financial capacity</span>
                                            <strong>{{ $financialScore }} / 100</strong>
                                        </div>
                                        <div class="app-score-track">
                                            <div
                                                class="app-score-fill"
                                                style="width: {{ $financialScore }}%;"
                                            ></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="app-score-top">
                                            <span>Housing readiness</span>
                                            <strong>{{ $housingScore }} / 100</strong>
                                        </div>
                                        <div class="app-score-track">
                                            <div
                                                class="app-score-fill"
                                                style="width: {{ $housingScore }}%;"
                                            ></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="app-score-top">
                                            <span>Parenting capacity</span>
                                            <strong>{{ $parentingScore }} / 100</strong>
                                        </div>
                                        <div class="app-score-track">
                                            <div
                                                class="app-score-fill"
                                                style="width: {{ $parentingScore }}%;"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="app-empty">
                                <div class="app-empty-icon">
                                    <i class="bi bi-person-lines-fill"></i>
                                </div>
                                <h3>No matching profile</h3>
                                <p>A parent matching profile has not yet been created for your account.</p>
                            </div>
                        @endif
                    </details>
                </div>
            </div>
        </section>
    @else
        <section class="app-empty" style="border: 1px solid var(--app-line); border-radius: 22px; background: #ffffff;">
            <div class="app-empty-icon">
                <i class="bi bi-folder-x"></i>
            </div>

            <h3>No active adoption case yet</h3>

            <p>
                Your account is active, but no adoption case has been assigned. Please wait for
                authorized AmoraCare staff to review or initialize your application.
            </p>

            <div class="app-actions" style="justify-content: center;">
                <a href="{{ route('parent.dashboard') }}" class="app-btn is-primary">
                    <i class="bi bi-grid"></i>
                    Return to dashboard
                </a>

                <a href="{{ route('parent.ai.index') }}" class="app-btn">
                    <i class="bi bi-chat-square-text"></i>
                    Ask AmoraCare Guide
                </a>
            </div>
        </section>
    @endif
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabs = Array.from(document.querySelectorAll("[data-app-tab]"));
    const panels = Array.from(document.querySelectorAll("[data-app-panel]"));
    const externalTabButtons = Array.from(
        document.querySelectorAll("[data-app-tab-open]")
    );

    function activateTab(name, focusTab = false) {
        const targetTab = tabs.find((tab) => tab.dataset.appTab === name);
        const targetPanel = panels.find(
            (panel) => panel.dataset.appPanel === name
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
            activateTab(tab.dataset.appTab || "overview");
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

            activateTab(tabs[nextIndex].dataset.appTab || "overview", true);
        });
    });

    externalTabButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const name = button.dataset.appTabOpen || "overview";
            activateTab(name);

            document.querySelector(".app-workspace")?.scrollIntoView({
                behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
                    ? "auto"
                    : "smooth",
                block: "start"
            });
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

    if (!tabs.some((tab) => tab.dataset.appTab === requestedSection)) {
        requestedSection = "overview";
    }

    activateTab(requestedSection);
});
</script>
@endsection
