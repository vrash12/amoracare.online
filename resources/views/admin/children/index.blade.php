{{-- resources/views/admin/children/index.blade.php --}}
@extends('layouts.dashboard', ['title' => 'Child Profiles'])

@section('content')
@php
    $latestMatchingResults = $latestMatchingResults ?? collect();
    $visibleChildren = $children->getCollection();

    $readyForMatchingCount = $visibleChildren
        ->filter(fn ($child) =>
            $child->case_status === 'available_for_adoption'
            && $child->adoption_eligibility_status === 'eligible'
        )
        ->count();

    $withRecommendationCount = $visibleChildren
        ->filter(fn ($child) => $latestMatchingResults->has($child->id))
        ->count();

    $attentionCount = $visibleChildren
        ->filter(fn ($child) =>
            $child->case_status === 'inactive'
            || in_array($child->adoption_eligibility_status, ['not_eligible', 'pending_documents'], true)
        )
        ->count();

    $queryParams = request()->except('page');

    $hasFilters = filled($search ?? null)
        || filled($caseStatus ?? null)
        || filled($eligibilityStatus ?? null);

    $caseStatusMeta = [
        'available_for_adoption' => ['label' => 'Available for adoption', 'class' => 'green', 'icon' => 'bi-check-circle'],
        'under_matching' => ['label' => 'Under matching', 'class' => 'blue', 'icon' => 'bi-diagram-3'],
        'matched' => ['label' => 'Matched', 'class' => 'purple', 'icon' => 'bi-link-45deg'],
        'adopted' => ['label' => 'Adopted', 'class' => 'gray', 'icon' => 'bi-house-heart'],
        'inactive' => ['label' => 'Inactive', 'class' => 'red', 'icon' => 'bi-slash-circle'],
    ];

    $eligibilityMeta = [
        'eligible' => ['label' => 'Eligible', 'class' => 'green', 'icon' => 'bi-shield-check'],
        'not_eligible' => ['label' => 'Not eligible', 'class' => 'red', 'icon' => 'bi-shield-x'],
        'pending_documents' => ['label' => 'Pending documents', 'class' => 'yellow', 'icon' => 'bi-file-earmark-clock'],
    ];

    $matchingStatusMeta = [
        'recommended' => ['label' => 'Recommended', 'class' => 'blue', 'icon' => 'bi-stars'],
        'converted_to_case' => ['label' => 'Converted to case', 'class' => 'green', 'icon' => 'bi-folder-check'],
        'accepted' => ['label' => 'Accepted', 'class' => 'green', 'icon' => 'bi-check2-circle'],
        'rejected' => ['label' => 'Rejected', 'class' => 'red', 'icon' => 'bi-x-circle'],
    ];
@endphp

<style>
    .children-admin {
        --primary: #8d3d27;
        --primary-dark: #6f2f1d;
        --primary-soft: #fff3ed;
        --blue: #2563eb;
        --green: #15803d;
        --yellow: #b45309;
        --red: #b91c1c;
        --purple: #7c3aed;
        --ink: #172033;
        --muted: #667085;
        --line: #e5e9f0;
        --soft: #f7f9fc;
        display: grid;
        gap: 18px;
        color: var(--ink);
    }

    .children-admin *,
    .children-admin *::before,
    .children-admin *::after { box-sizing: border-box; }

    .children-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
        padding: 27px;
        overflow: hidden;
        border: 1px solid #efd6ca;
        border-radius: 24px;
        background:
            radial-gradient(circle at 90% 8%, rgba(141,61,39,.14), transparent 31%),
            linear-gradient(135deg, #fffaf7 0%, #fff 58%, #f8fafc 100%);
        box-shadow: 0 18px 42px rgba(20,31,51,.07);
    }

    .children-eyebrow,
    .children-meta,
    .children-badge,
    .children-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .children-eyebrow {
        margin-bottom: 10px;
        padding: 7px 11px;
        border: 1px solid #edc7b8;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .children-hero h1 {
        margin: 0;
        color: #101828;
        font-size: clamp(28px, 3.2vw, 40px);
        line-height: 1.15;
    }

    .children-hero p {
        max-width: 780px;
        margin: 10px 0 0;
        color: var(--muted);
        line-height: 1.7;
    }

    .children-meta-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .children-meta {
        padding: 8px 11px;
        border: 1px solid var(--line);
        background: rgba(255,255,255,.9);
        color: #475467;
        font-size: 12px;
        font-weight: 850;
    }

    .children-hero-actions {
        display: grid;
        gap: 9px;
        min-width: 218px;
    }

    .children-btn {
        min-height: 41px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 13px;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        background: #fff;
        color: #344054;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        transition: .16s ease;
    }

    .children-btn:hover { background: #f9fafb; color: #101828; }
    .children-btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
    .children-btn.primary:hover { border-color: var(--primary-dark); background: var(--primary-dark); }
    .children-btn.blue { border-color: var(--blue); background: var(--blue); color: #fff; }
    .children-btn.blue:hover { background: #1d4ed8; }
    .children-btn.green { border-color: var(--green); background: var(--green); color: #fff; }
    .children-btn.green:hover { background: #166534; }
    .children-btn.red { border-color: var(--red); background: var(--red); color: #fff; }
    .children-btn.red:hover { background: #991b1b; }
    .children-btn.small { min-height: 36px; padding: 0 10px; font-size: 12px; }
    .children-btn:disabled { opacity: .58; cursor: not-allowed; }

    .children-privacy {
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

    .children-privacy-icon {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #ffedd5;
        color: #c2410c;
        font-size: 18px;
    }

    .children-privacy strong { display: block; margin-bottom: 2px; color: #8a3e08; }

    .children-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .children-stat {
        min-width: 0;
        padding: 17px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(20,31,51,.045);
    }

    .children-stat-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
    .children-stat-label { color: var(--muted); font-size: 11px; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; }
    .children-stat-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px; background: #eff6ff; color: var(--blue); font-size: 18px; }
    .children-stat.ready .children-stat-icon { background: #ecfdf3; color: var(--green); }
    .children-stat.match .children-stat-icon { background: #f5f3ff; color: var(--purple); }
    .children-stat.attention .children-stat-icon { background: #fff1f2; color: var(--red); }
    .children-stat-value { margin-top: 12px; color: #101828; font-size: 29px; font-weight: 950; line-height: 1; }
    .children-stat-help { margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.45; }

    .children-toolbar,
    .children-results {
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 21px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(20,31,51,.055);
    }

    .children-toolbar-head,
    .children-results-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 18px 20px;
        border-bottom: 1px solid var(--line);
    }

    .children-toolbar-title { display: flex; gap: 10px; align-items: center; }
    .children-toolbar-icon { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 13px; background: #eff6ff; color: var(--blue); }
    .children-toolbar-title strong,
    .children-results-head h2 { margin: 0; color: #101828; }
    .children-toolbar-title span,
    .children-results-head p { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; line-height: 1.5; }

    .children-filter-toggle {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid #d0d5dd;
        border-radius: 11px;
        background: #fff;
        color: #344054;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .children-filter-toggle.active { border-color: var(--blue); background: #eff6ff; color: #1d4ed8; }
    .children-filter-body { padding: 18px; }
    .children-filter-body[hidden] { display: none; }

    .children-filter-form {
        display: grid;
        grid-template-columns: minmax(240px, 1.35fr) minmax(185px, .75fr) minmax(185px, .75fr) auto;
        gap: 12px;
        align-items: end;
    }

    .children-field label { display: block; margin-bottom: 7px; color: #344054; font-size: 12px; font-weight: 900; }
    .children-field input,
    .children-field select {
        width: 100%;
        min-height: 43px;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        padding: 0 12px;
        background: #fff;
        color: #101828;
        font: inherit;
        outline: none;
    }

    .children-field input:focus,
    .children-field select:focus,
    .children-quick-search input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 4px rgba(37,99,235,.11);
    }

    .children-filter-actions { display: flex; gap: 8px; }

    .children-quick-row {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 14px 18px;
        border-top: 1px solid var(--line);
        background: #fbfcfe;
    }

    .children-quick-search { position: relative; }
    .children-quick-search > i { position: absolute; top: 50%; left: 14px; color: #98a2b3; transform: translateY(-50%); }
    .children-quick-search input { width: 100%; min-height: 42px; padding: 0 42px 0 40px; border: 1px solid #d0d5dd; border-radius: 12px; outline: none; }
    .children-search-clear { position: absolute; top: 50%; right: 7px; width: 31px; height: 31px; display: none; place-items: center; border: 0; border-radius: 9px; background: transparent; color: #667085; cursor: pointer; transform: translateY(-50%); }
    .children-search-clear.visible { display: grid; }

    .children-view-controls { display: flex; gap: 7px; align-items: center; }
    .children-count { color: var(--muted); font-size: 12px; font-weight: 800; white-space: nowrap; }
    .children-view-button { width: 40px; height: 40px; display: grid; place-items: center; border: 1px solid #d0d5dd; border-radius: 11px; background: #fff; color: #667085; cursor: pointer; }
    .children-view-button.active { border-color: var(--blue); background: #eff6ff; color: var(--blue); }

    .children-results-head h2 { font-size: 21px; }
    .children-list-badge { padding: 7px 10px; border: 1px solid var(--line); border-radius: 999px; background: var(--soft); color: #475467; font-size: 11px; font-weight: 900; white-space: nowrap; }

    .children-cards { display: grid; gap: 12px; padding: 16px; }
    .children-cards.grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    .children-card {
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: #fff;
        transition: .16s ease;
    }

    .children-card:hover { border-color: #cbd5e1; box-shadow: 0 12px 28px rgba(15,23,42,.07); transform: translateY(-1px); }
    .children-card[hidden] { display: none !important; }

    .children-card-main {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: start;
        padding: 17px;
    }

    .children-avatar { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 17px; background: linear-gradient(135deg, #eef2ff, #dbeafe); color: #3730a3; font-size: 17px; font-weight: 950; }
    .children-card-copy { min-width: 0; }
    .children-card-title-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .children-card h3 { margin: 0; color: #101828; font-size: 16px; font-weight: 950; line-height: 1.35; }
    .children-code { padding: 5px 8px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: 10px; font-weight: 900; }
    .children-card-meta { display: flex; gap: 7px 13px; flex-wrap: wrap; margin-top: 7px; color: var(--muted); font-size: 11px; }
    .children-card-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .children-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }

    .children-badge,
    .children-status { padding: 6px 8px; font-size: 10px; font-weight: 900; }
    .green { background: #dcfce7; color: #166534; }
    .blue { background: #dbeafe; color: #1d4ed8; }
    .purple { background: #ede9fe; color: #6d28d9; }
    .yellow { background: #fef3c7; color: #92400e; }
    .red { background: #fee2e2; color: #991b1b; }
    .gray { background: #f2f4f7; color: #475467; }

    .children-card-actions { display: flex; gap: 7px; justify-content: flex-end; flex-wrap: wrap; }

    .children-match {
        margin: 0 17px 17px;
        padding: 14px;
        border: 1px solid #d8e3f8;
        border-radius: 15px;
        background: #f7faff;
    }

    .children-match.empty { border-color: var(--line); background: var(--soft); }
    .children-match-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap; }
    .children-match-label { color: var(--muted); font-size: 10px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .children-match-parent { margin-top: 4px; color: #101828; font-size: 14px; font-weight: 950; }
    .children-match-email,
    .children-match-run { margin-top: 3px; color: var(--muted); font-size: 11px; overflow-wrap: anywhere; }

    .children-score-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
    .children-score { min-width: 0; padding: 10px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
    .children-score-label { color: var(--muted); font-size: 9px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .children-score-value { margin-top: 3px; color: #101828; font-size: 17px; font-weight: 950; }

    .children-match-empty { display: flex; gap: 10px; align-items: flex-start; }
    .children-match-empty-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px; background: #f2f4f7; color: #667085; }
    .children-match-empty strong { display: block; color: #344054; font-size: 13px; }
    .children-match-empty p { margin: 4px 0 0; color: var(--muted); font-size: 11px; line-height: 1.5; }

    .children-details { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; padding: 0 17px 17px; }
    .children-details[hidden] { display: none; }
    .children-detail-box { min-width: 0; padding: 12px; border: 1px solid var(--line); border-radius: 13px; background: var(--soft); }
    .children-detail-label { display: block; margin-bottom: 5px; color: #667085; font-size: 9px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .children-detail-value { color: #344054; font-size: 12px; line-height: 1.55; overflow-wrap: anywhere; }

    .children-empty { display: none; padding: 44px 20px; text-align: center; color: var(--muted); }
    .children-empty.visible { display: block; }
    .children-empty-icon { width: 62px; height: 62px; display: grid; place-items: center; margin: 0 auto 13px; border-radius: 20px; background: #f2f4f7; color: #667085; font-size: 27px; }
    .children-empty h3 { margin: 0; color: #101828; }
    .children-empty p { max-width: 620px; margin: 7px auto 0; line-height: 1.65; }

    .children-pagination { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; padding: 16px 18px; border-top: 1px solid var(--line); background: #fbfcfe; }
    .children-pagination-info { color: var(--muted); font-size: 12px; font-weight: 800; }
    .children-pagination-links { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .children-page-link,
    .children-page-active,
    .children-page-disabled { min-width: 38px; height: 38px; padding: 0 11px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 11px; font-size: 12px; font-weight: 900; text-decoration: none; }
    .children-page-link { background: #fff; color: #344054; }
    .children-page-link:hover { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
    .children-page-active { border-color: var(--primary); background: var(--primary); color: #fff; }
    .children-page-disabled { background: #f9fafb; color: #98a2b3; cursor: not-allowed; }

    .children-modal[hidden] { display: none !important; }
    .children-modal { position: fixed; inset: 0; z-index: 1300; display: grid; place-items: center; padding: 20px; }
    .children-modal-backdrop { position: absolute; inset: 0; border: 0; background: rgba(15,23,42,.64); backdrop-filter: blur(5px); }
    .children-modal-dialog { position: relative; z-index: 1; width: min(100%, 570px); max-height: min(88vh, 720px); overflow-y: auto; border: 1px solid #e4e7ec; border-radius: 22px; background: #fff; box-shadow: 0 28px 70px rgba(15,23,42,.28); }
    .children-modal-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; padding: 20px 21px 16px; border-bottom: 1px solid var(--line); }
    .children-modal-heading { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 11px; align-items: center; }
    .children-modal-icon { width: 45px; height: 45px; display: grid; place-items: center; border-radius: 14px; background: #eff6ff; color: var(--blue); font-size: 20px; }
    .children-modal-head h2 { margin: 0; color: #101828; font-size: 20px; }
    .children-modal-head p { margin: 5px 0 0; color: var(--muted); font-size: 12px; line-height: 1.5; }
    .children-modal-close { width: 38px; height: 38px; display: grid; place-items: center; border: 1px solid #d0d5dd; border-radius: 11px; background: #fff; color: #475467; cursor: pointer; }
    .children-modal-body { display: grid; gap: 14px; padding: 20px 21px; }
    .children-modal-notice { display: flex; gap: 10px; align-items: flex-start; padding: 12px 13px; border: 1px solid #bfdbfe; border-radius: 13px; background: #eff6ff; color: #1e40af; font-size: 12px; line-height: 1.55; }
    .children-modal-notice.danger { border-color: #fecaca; background: #fff1f2; color: #9f1239; }
    .children-modal-list { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
    .children-modal-list li { display: flex; gap: 8px; align-items: flex-start; color: #475467; font-size: 12px; line-height: 1.5; }
    .children-modal-list i { margin-top: 2px; color: var(--green); }
    .children-modal-foot { display: flex; justify-content: flex-end; gap: 9px; padding: 16px 21px 20px; border-top: 1px solid var(--line); }
    body.children-modal-open { overflow: hidden; }

    @media (max-width: 1120px) {
        .children-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .children-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .children-filter-actions { grid-column: span 2; }
        .children-cards.grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 760px) {
        .children-hero { grid-template-columns: 1fr; padding: 21px; }
        .children-hero-actions { min-width: 0; }
        .children-stats,
        .children-filter-form,
        .children-quick-row { grid-template-columns: 1fr; }
        .children-filter-actions { grid-column: auto; }
        .children-card-main { grid-template-columns: auto minmax(0, 1fr); }
        .children-card-actions { grid-column: 1 / -1; justify-content: stretch; }
        .children-card-actions .children-btn { flex: 1 1 100px; }
        .children-score-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .children-details { grid-template-columns: 1fr; }
        .children-modal { align-items: end; padding: 0; }
        .children-modal-dialog { width: 100%; max-height: 92vh; border-radius: 22px 22px 0 0; }
    }

    @media (max-width: 480px) {
        .children-hero-actions,
        .children-hero-actions .children-btn,
        .children-filter-actions,
        .children-filter-actions .children-btn { width: 100%; }
        .children-filter-actions { display: grid; grid-template-columns: 1fr; }
        .children-card-main { grid-template-columns: 1fr; }
        .children-card-actions { grid-column: auto; }
        .children-modal-foot { display: grid; grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="children-admin">
    <section class="children-hero">
        <div>
            <div class="children-eyebrow">
                <i class="bi bi-person-hearts"></i>
                Confidential child records
            </div>

            <h1>Child Profiles</h1>

            <p>
                Maintain authorized child records, monitor legal eligibility, and review
                Gale–Shapley recommendations as decision-support information—not final placements.
            </p>

            <div class="children-meta-row">
                <span class="children-meta"><i class="bi bi-shield-lock"></i> Admin-only workspace</span>
                <span class="children-meta"><i class="bi bi-files"></i> {{ $children->total() }} total profiles</span>
                @if($hasFilters)
                    <span class="children-meta"><i class="bi bi-funnel"></i> Filters applied</span>
                @endif
            </div>
        </div>

        <div class="children-hero-actions">
            <a href="{{ route('admin.children.create') }}" class="children-btn primary">
                <i class="bi bi-plus-circle"></i>
                Add child profile
            </a>

            <button
                type="button"
                class="children-btn blue"
                data-child-action-open
                data-action-mode="run-matching"
                data-action-url="{{ route('admin.matching.run') }}"
                data-action-title="Run Gale–Shapley matching"
                data-action-subtitle="Create a new recommendation batch"
                data-action-message="The system will evaluate currently eligible and available child records against qualified prospective parent profiles."
            >
                <i class="bi bi-diagram-3"></i>
                Run matching
            </button>
        </div>
    </section>

    <section class="children-privacy">
        <div class="children-privacy-icon"><i class="bi bi-shield-lock"></i></div>
        <div>
            <strong>Child records and matching results are highly restricted.</strong>
            Never expose full child profiles, rankings, scores, medical details, or matching recommendations
            to prospective parents or unauthorized users. Human review remains mandatory before any case or placement action.
        </div>
    </section>

    <section class="children-stats">
        <article class="children-stat">
            <div class="children-stat-top">
                <span class="children-stat-label">Visible on page</span>
                <span class="children-stat-icon"><i class="bi bi-people"></i></span>
            </div>
            <div class="children-stat-value">{{ $visibleChildren->count() }}</div>
            <div class="children-stat-help">Profiles in the current paginated result</div>
        </article>

        <article class="children-stat ready">
            <div class="children-stat-top">
                <span class="children-stat-label">Ready for matching</span>
                <span class="children-stat-icon"><i class="bi bi-check2-circle"></i></span>
            </div>
            <div class="children-stat-value">{{ $readyForMatchingCount }}</div>
            <div class="children-stat-help">Eligible and available for adoption</div>
        </article>

        <article class="children-stat match">
            <div class="children-stat-top">
                <span class="children-stat-label">With recommendation</span>
                <span class="children-stat-icon"><i class="bi bi-stars"></i></span>
            </div>
            <div class="children-stat-value">{{ $withRecommendationCount }}</div>
            <div class="children-stat-help">Profiles with a latest matching result</div>
        </article>

        <article class="children-stat attention">
            <div class="children-stat-top">
                <span class="children-stat-label">Need attention</span>
                <span class="children-stat-icon"><i class="bi bi-exclamation-circle"></i></span>
            </div>
            <div class="children-stat-value">{{ $attentionCount }}</div>
            <div class="children-stat-help">Inactive, ineligible, or pending requirements</div>
        </article>
    </section>

    <section class="children-toolbar">
        <div class="children-toolbar-head">
            <div class="children-toolbar-title">
                <div class="children-toolbar-icon"><i class="bi bi-funnel"></i></div>
                <div>
                    <strong>Search and filter records</strong>
                    <span>Server filters search the complete dataset; quick search filters this page only.</span>
                </div>
            </div>

            <button
                type="button"
                class="children-filter-toggle {{ $hasFilters ? 'active' : '' }}"
                id="childrenFilterToggle"
                aria-expanded="{{ $hasFilters ? 'true' : 'false' }}"
                aria-controls="childrenFilterBody"
            >
                <i class="bi bi-sliders"></i>
                {{ $hasFilters ? 'Filters active' : 'Show filters' }}
                <i class="bi bi-chevron-down" id="childrenFilterChevron"></i>
            </button>
        </div>

        <div class="children-filter-body" id="childrenFilterBody" @if(!$hasFilters) hidden @endif>
            <form method="GET" action="{{ route('admin.children.index') }}" class="children-filter-form">
                <div class="children-field">
                    <label for="search">Search all profiles</label>
                    <input type="search" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Child code, full name, or nickname">
                </div>

                <div class="children-field">
                    <label for="case_status">Case status</label>
                    <select id="case_status" name="case_status">
                        <option value="">All statuses</option>
                        @foreach($caseStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($caseStatus ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="children-field">
                    <label for="adoption_eligibility_status">Eligibility</label>
                    <select id="adoption_eligibility_status" name="adoption_eligibility_status">
                        <option value="">All eligibility states</option>
                        @foreach($eligibilityStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($eligibilityStatus ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="children-filter-actions">
                    <button type="submit" class="children-btn blue"><i class="bi bi-search"></i> Apply filters</button>
                    @if($hasFilters)
                        <a href="{{ route('admin.children.index') }}" class="children-btn"><i class="bi bi-x-circle"></i> Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="children-quick-row">
            <div class="children-quick-search">
                <i class="bi bi-search"></i>
                <label for="childrenQuickSearch" class="visually-hidden">Search current page</label>
                <input type="search" id="childrenQuickSearch" placeholder="Quick-search the current page..." autocomplete="off">
                <button type="button" class="children-search-clear" id="childrenQuickSearchClear" aria-label="Clear quick search"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="children-view-controls">
                <span class="children-count"><span id="childrenVisibleCount">{{ $visibleChildren->count() }}</span> shown</span>
                <button type="button" class="children-view-button" id="childrenListView" title="List view"><i class="bi bi-list"></i></button>
                <button type="button" class="children-view-button active" id="childrenGridView" title="Grid view"><i class="bi bi-grid"></i></button>
            </div>
        </div>
    </section>

    <section class="children-results">
        <header class="children-results-head">
            <div>
                <h2>Authorized child records</h2>
                <p>Open a profile to review its complete authorized record. Matching details are administrative recommendations only.</p>
            </div>
            <span class="children-list-badge"><i class="bi bi-shield-check"></i> Human review required</span>
        </header>

        @if($children->count())
            <div class="children-cards grid" id="childrenCards">
                @foreach($children as $child)
                    @php
                        $matchingResult = $latestMatchingResults->get($child->id);

                        $isReadyForMatching =
                            $child->case_status === 'available_for_adoption'
                            && $child->adoption_eligibility_status === 'eligible';

                        $caseMeta = $caseStatusMeta[$child->case_status] ?? [
                            'label' => $child->case_status_label ?? ucfirst((string) $child->case_status),
                            'class' => 'gray',
                            'icon' => 'bi-info-circle',
                        ];

                        $eligibleMeta = $eligibilityMeta[$child->adoption_eligibility_status] ?? [
                            'label' => $child->eligibility_status_label ?? ucfirst((string) $child->adoption_eligibility_status),
                            'class' => 'gray',
                            'icon' => 'bi-info-circle',
                        ];

                        $matchMeta = $matchingStatusMeta[$matchingResult?->status] ?? [
                            'label' => $matchingResult ? ucwords(str_replace('_', ' ', (string) $matchingResult->status)) : 'No recommendation',
                            'class' => 'gray',
                            'icon' => 'bi-dash-circle',
                        ];

                        $initials = collect(preg_split('/\s+/', trim((string) $child->full_name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');

                        $searchText = strtolower(implode(' ', array_filter([
                            $child->child_code,
                            $child->full_name,
                            $child->nickname,
                            $child->sex,
                            $child->case_status,
                            $child->case_status_label ?? null,
                            $child->adoption_eligibility_status,
                            $child->eligibility_status_label ?? null,
                            $child->creator?->name,
                            $child->updater?->name,
                            $matchingResult?->prospectiveParent?->name,
                            $matchingResult?->prospectiveParent?->email,
                            $matchingResult?->run?->run_code,
                            $matchingResult?->status,
                        ])));
                    @endphp

                    <article class="children-card" data-child-card data-search="{{ $searchText }}">
                        <div class="children-card-main">
                            <div class="children-avatar">{{ $initials ?: 'CP' }}</div>

                            <div class="children-card-copy">
                                <div class="children-card-title-row">
                                    <h3>{{ $child->full_name }}</h3>
                                    <span class="children-code">{{ $child->child_code }}</span>
                                </div>

                                <div class="children-card-meta">
                                    @if($child->nickname)
                                        <span><i class="bi bi-person-badge"></i> {{ $child->nickname }}</span>
                                    @endif
                                    <span><i class="bi bi-gender-ambiguous"></i> {{ ucfirst($child->sex) }}</span>
                                    <span><i class="bi bi-person-check"></i> Created by {{ $child->creator?->name ?? 'N/A' }}</span>
                                </div>

                                <div class="children-badges">
                                    <span class="children-badge {{ $caseMeta['class'] }}"><i class="bi {{ $caseMeta['icon'] }}"></i> {{ $caseMeta['label'] }}</span>
                                    <span class="children-badge {{ $eligibleMeta['class'] }}"><i class="bi {{ $eligibleMeta['icon'] }}"></i> {{ $eligibleMeta['label'] }}</span>
                                    @if($child->is_special_needs)
                                        <span class="children-badge yellow"><i class="bi bi-universal-access"></i> Special needs</span>
                                    @endif
                                </div>
                            </div>

                            <div class="children-card-actions">
                                <a href="{{ route('admin.children.show', $child) }}" class="children-btn small"><i class="bi bi-eye"></i> View</a>
                                <a href="{{ route('admin.children.edit', $child) }}" class="children-btn small"><i class="bi bi-pencil-square"></i> Edit</a>
                                <button type="button" class="children-btn small" data-child-details-toggle="{{ $child->id }}" aria-expanded="false" aria-controls="childrenDetails{{ $child->id }}"><i class="bi bi-info-circle"></i> Details</button>
                                <button
                                    type="button"
                                    class="children-btn small"
                                    data-child-action-open
                                    data-action-mode="delete-child"
                                    data-action-url="{{ route('admin.children.destroy', $child) }}"
                                    data-action-title="Delete child profile"
                                    data-action-subtitle="{{ $child->child_code }} · {{ $child->full_name }}"
                                    data-action-message="This action will soft-delete the child profile. The record may remain recoverable according to your application retention rules."
                                ><i class="bi bi-trash"></i> Delete</button>
                            </div>
                        </div>

                        @if($matchingResult)
                            <div class="children-match">
                                <div class="children-match-head">
                                    <div>
                                        <div class="children-match-label">Latest matching recommendation</div>
                                        <div class="children-match-parent">{{ $matchingResult->prospectiveParent?->name ?? 'No parent found' }}</div>
                                        <div class="children-match-email">{{ $matchingResult->prospectiveParent?->email ?? 'No email available' }}</div>
                                        <div class="children-match-run">Run {{ $matchingResult->run?->run_code ?? 'N/A' }}</div>
                                    </div>
                                    <span class="children-status {{ $matchMeta['class'] }}"><i class="bi {{ $matchMeta['icon'] }}"></i> {{ $matchMeta['label'] }}</span>
                                </div>

                                <div class="children-score-grid">
                                    <div class="children-score"><div class="children-score-label">Child score</div><div class="children-score-value">{{ $matchingResult->child_score }}</div></div>
                                    <div class="children-score"><div class="children-score-label">Parent score</div><div class="children-score-value">{{ $matchingResult->parent_score }}</div></div>
                                    <div class="children-score"><div class="children-score-label">Child rank</div><div class="children-score-value">{{ $matchingResult->rank_for_child ?? 'N/A' }}</div></div>
                                    <div class="children-score"><div class="children-score-label">Parent rank</div><div class="children-score-value">{{ $matchingResult->rank_for_parent ?? 'N/A' }}</div></div>
                                </div>

                                @if($matchingResult->status === 'recommended')
                                    <div style="display:flex; justify-content:flex-end; margin-top:12px;">
                                        <button
                                            type="button"
                                            class="children-btn green small"
                                            data-child-action-open
                                            data-action-mode="create-case"
                                            data-action-url="{{ route('admin.matching.create-case', $matchingResult) }}"
                                            data-action-title="Create adoption case"
                                            data-action-subtitle="{{ $child->child_code }} · {{ $matchingResult->prospectiveParent?->name ?? 'Recommended parent' }}"
                                            data-action-message="This converts the recommendation into an adoption case for further authorized review. It does not finalize placement or adoption."
                                        ><i class="bi bi-folder-plus"></i> Create adoption case</button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="children-match empty">
                                <div class="children-match-empty">
                                    <div class="children-match-empty-icon"><i class="bi {{ $isReadyForMatching ? 'bi-diagram-3' : 'bi-dash-circle' }}"></i></div>
                                    <div>
                                        <strong>{{ $isReadyForMatching ? 'Ready for matching' : 'No active recommendation' }}</strong>
                                        <p>{{ $isReadyForMatching ? 'Run matching to generate a staff-review recommendation.' : 'This child is not currently both eligible and available for adoption.' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="children-details" id="childrenDetails{{ $child->id }}" hidden>
                            <div class="children-detail-box"><span class="children-detail-label">Created by</span><div class="children-detail-value">{{ $child->creator?->name ?? 'N/A' }}</div></div>
                            <div class="children-detail-box"><span class="children-detail-label">Last updated by</span><div class="children-detail-value">{{ $child->updater?->name ?? 'N/A' }}</div></div>
                            <div class="children-detail-box"><span class="children-detail-label">Matching readiness</span><div class="children-detail-value">{{ $isReadyForMatching ? 'Eligible and available for adoption' : 'Not currently ready for matching' }}</div></div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="children-empty" id="childrenNoResults">
                <div class="children-empty-icon"><i class="bi bi-search"></i></div>
                <h3>No records match the quick search</h3>
                <p>Clear the current-page search or use the full filters to search the complete dataset.</p>
            </div>
        @else
            <div class="children-empty visible">
                <div class="children-empty-icon"><i class="bi bi-person-x"></i></div>
                <h3>No child profiles found</h3>
                <p>Adjust the filters or create a new authorized child profile.</p>
                <div style="margin-top:16px;"><a href="{{ route('admin.children.create') }}" class="children-btn primary"><i class="bi bi-plus-circle"></i> Add child profile</a></div>
            </div>
        @endif

        @if($children->hasPages())
            <div class="children-pagination">
                <div class="children-pagination-info">Showing {{ $children->firstItem() }} to {{ $children->lastItem() }} of {{ $children->total() }} records</div>
                <div class="children-pagination-links">
                    @if($children->onFirstPage())
                        <span class="children-page-disabled"><i class="bi bi-chevron-left"></i> Previous</span>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $children->currentPage() - 1])) }}" class="children-page-link"><i class="bi bi-chevron-left"></i> Previous</a>
                    @endif

                    @php
                        $start = max(1, $children->currentPage() - 2);
                        $end = min($children->lastPage(), $children->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => 1])) }}" class="children-page-link">1</a>
                        @if($start > 2)<span class="children-page-disabled">…</span>@endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page === $children->currentPage())
                            <span class="children-page-active">{{ $page }}</span>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $page])) }}" class="children-page-link">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $children->lastPage())
                        @if($end < $children->lastPage() - 1)<span class="children-page-disabled">…</span>@endif
                        <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $children->lastPage()])) }}" class="children-page-link">{{ $children->lastPage() }}</a>
                    @endif

                    @if($children->hasMorePages())
                        <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $children->currentPage() + 1])) }}" class="children-page-link">Next <i class="bi bi-chevron-right"></i></a>
                    @else
                        <span class="children-page-disabled">Next <i class="bi bi-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @else
            <div class="children-pagination">
                <div class="children-pagination-info">Showing {{ $children->count() }} {{ Str::plural('record', $children->count()) }}</div>
            </div>
        @endif
    </section>
</div>

<div class="children-modal" id="childrenActionModal" hidden role="dialog" aria-modal="true" aria-labelledby="childrenActionTitle">
    <button type="button" class="children-modal-backdrop" data-child-action-close aria-label="Close confirmation dialog"></button>

    <div class="children-modal-dialog">
        <form method="POST" id="childrenActionForm" action="">
            @csrf
            <input type="hidden" name="_method" id="childrenActionMethod" value="POST">

            <header class="children-modal-head">
                <div class="children-modal-heading">
                    <div class="children-modal-icon" id="childrenActionIcon"><i class="bi bi-check2-square"></i></div>
                    <div>
                        <h2 id="childrenActionTitle">Confirm action</h2>
                        <p id="childrenActionSubtitle">Authorized child record action</p>
                    </div>
                </div>
                <button type="button" class="children-modal-close" data-child-action-close aria-label="Close confirmation dialog"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="children-modal-body">
                <div class="children-modal-notice" id="childrenActionNotice">
                    <i class="bi bi-info-circle"></i>
                    <span id="childrenActionMessage">Review the action carefully before continuing.</span>
                </div>

                <ul class="children-modal-list">
                    <li><i class="bi bi-check2-circle"></i><span>This action will be recorded under your authenticated account.</span></li>
                    <li><i class="bi bi-check2-circle"></i><span>Matching results remain recommendations requiring human review.</span></li>
                    <li><i class="bi bi-check2-circle"></i><span>Child privacy and welfare considerations must remain the priority.</span></li>
                </ul>
            </div>

            <footer class="children-modal-foot">
                <button type="button" class="children-btn" data-child-action-close>Cancel</button>
                <button type="submit" class="children-btn primary" id="childrenActionSubmit">
                    <i class="bi bi-check2-circle"></i>
                    <span id="childrenActionSubmitText">Confirm action</span>
                </button>
            </footer>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterToggle = document.getElementById('childrenFilterToggle');
    const filterBody = document.getElementById('childrenFilterBody');
    const filterChevron = document.getElementById('childrenFilterChevron');

    filterToggle?.addEventListener('click', function () {
        const opening = filterBody?.hidden;
        if (!filterBody) return;
        filterBody.hidden = !opening;
        filterToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
        if (filterChevron) filterChevron.className = opening ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
    });

    const cardsContainer = document.getElementById('childrenCards');
    const cards = Array.from(document.querySelectorAll('[data-child-card]'));
    const quickSearch = document.getElementById('childrenQuickSearch');
    const quickSearchClear = document.getElementById('childrenQuickSearchClear');
    const visibleCount = document.getElementById('childrenVisibleCount');
    const noResults = document.getElementById('childrenNoResults');
    const gridButton = document.getElementById('childrenGridView');
    const listButton = document.getElementById('childrenListView');

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function applyQuickSearch() {
        const term = normalize(quickSearch?.value);
        let count = 0;

        cards.forEach((card) => {
            const visible = !term || normalize(card.dataset.search).includes(term);
            card.hidden = !visible;
            if (visible) count += 1;
        });

        if (visibleCount) visibleCount.textContent = String(count);
        noResults?.classList.toggle('visible', count === 0);
        quickSearchClear?.classList.toggle('visible', Boolean(quickSearch?.value));
    }

    quickSearch?.addEventListener('input', applyQuickSearch);
    quickSearchClear?.addEventListener('click', function () {
        if (!quickSearch) return;
        quickSearch.value = '';
        quickSearch.focus();
        applyQuickSearch();
    });

    function setView(mode) {
        if (!cardsContainer) return;
        const grid = mode === 'grid';
        cardsContainer.classList.toggle('grid', grid);
        gridButton?.classList.toggle('active', grid);
        listButton?.classList.toggle('active', !grid);
        try { localStorage.setItem('amoraChildrenView', mode); } catch (error) {}
    }

    gridButton?.addEventListener('click', () => setView('grid'));
    listButton?.addEventListener('click', () => setView('list'));

    try {
        const saved = localStorage.getItem('amoraChildrenView');
        if (saved === 'list' || saved === 'grid') setView(saved);
    } catch (error) {}

    document.querySelectorAll('[data-child-details-toggle]').forEach((button) => {
        button.addEventListener('click', function () {
            const details = document.getElementById('childrenDetails' + button.dataset.childDetailsToggle);
            if (!details) return;
            const opening = details.hidden;
            details.hidden = !opening;
            button.setAttribute('aria-expanded', opening ? 'true' : 'false');
            button.innerHTML = opening
                ? '<i class="bi bi-chevron-up"></i> Hide details'
                : '<i class="bi bi-info-circle"></i> Details';
        });
    });

    applyQuickSearch();

    const modal = document.getElementById('childrenActionModal');
    const form = document.getElementById('childrenActionForm');
    const method = document.getElementById('childrenActionMethod');
    const title = document.getElementById('childrenActionTitle');
    const subtitle = document.getElementById('childrenActionSubtitle');
    const message = document.getElementById('childrenActionMessage');
    const icon = document.getElementById('childrenActionIcon');
    const notice = document.getElementById('childrenActionNotice');
    const submit = document.getElementById('childrenActionSubmit');
    const submitText = document.getElementById('childrenActionSubmitText');
    let lastFocused = null;

    const modes = {
        'run-matching': { method: 'POST', icon: 'bi-diagram-3', className: 'children-btn blue', text: 'Run matching', danger: false },
        'create-case': { method: 'POST', icon: 'bi-folder-plus', className: 'children-btn green', text: 'Create adoption case', danger: false },
        'delete-child': { method: 'DELETE', icon: 'bi-trash', className: 'children-btn red', text: 'Delete profile', danger: true }
    };

    function openModal(button) {
        if (!modal || !form) return;
        lastFocused = document.activeElement;
        const config = modes[button.dataset.actionMode] || modes['run-matching'];

        form.action = button.dataset.actionUrl || '';
        if (method) method.value = config.method;
        if (title) title.textContent = button.dataset.actionTitle || 'Confirm action';
        if (subtitle) subtitle.textContent = button.dataset.actionSubtitle || 'Authorized child record action';
        if (message) message.textContent = button.dataset.actionMessage || 'Review the action carefully before continuing.';
        if (icon) icon.innerHTML = `<i class="bi ${config.icon}"></i>`;
        if (submit) {
            submit.className = config.className;
            submit.disabled = false;
        }
        if (submitText) submitText.textContent = config.text;
        notice?.classList.toggle('danger', config.danger);

        modal.hidden = false;
        document.body.classList.add('children-modal-open');
        setTimeout(() => submit?.focus(), 50);
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('children-modal-open');
        if (form) form.action = '';
        if (method) method.value = 'POST';
        lastFocused?.focus?.();
    }

    document.querySelectorAll('[data-child-action-open]').forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    document.querySelectorAll('[data-child-action-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    form?.addEventListener('submit', function () {
        if (submit) submit.disabled = true;
        if (submitText) submitText.textContent = 'Processing...';
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
});
</script>
@endsection
