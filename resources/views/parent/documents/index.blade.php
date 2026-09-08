@extends('layouts.dashboard', ['title' => 'Required Documents'])

@section('content')
@php
    $safeProgress = max(0, min(100, (int) $documentProgressPercent));

    $documentUploadsLocked = $adoptionCase
        && (
            $adoptionCase->racco_review_status === 'approved'
            || in_array($adoptionCase->status, ['finalized', 'closed', 'cancelled'], true)
        );

    $statusMeta = [
        'pending' => [
            'label' => 'Not submitted',
            'icon' => 'bi-clock',
            'class' => 'is-pending',
            'description' => 'Upload the requested document when it is ready.',
        ],
        'submitted' => [
            'label' => 'Submitted',
            'icon' => 'bi-cloud-check',
            'class' => 'is-submitted',
            'description' => 'Your file was received and is waiting for review.',
        ],
        'under_review' => [
            'label' => 'Under review',
            'icon' => 'bi-search',
            'class' => 'is-review',
            'description' => 'Authorized staff are currently reviewing this file.',
        ],
        'verified' => [
            'label' => 'Verified',
            'icon' => 'bi-patch-check',
            'class' => 'is-verified',
            'description' => 'This file was accepted. You may still replace it before final RACCO case approval; a replacement will require a new review.',
        ],
        'rejected' => [
            'label' => 'Needs revision',
            'icon' => 'bi-exclamation-triangle',
            'class' => 'is-rejected',
            'description' => 'Review the remarks and upload a corrected file.',
        ],
        'expired' => [
            'label' => 'Expired',
            'icon' => 'bi-calendar-x',
            'class' => 'is-expired',
            'description' => 'Upload a renewed or updated copy of this document.',
        ],
    ];

    $needsActionStatuses = ['pending', 'rejected', 'expired'];

    $needsActionCount = $documents
        ->whereIn('status', $needsActionStatuses)
        ->count();

    $waitingCount = $documents
        ->whereIn('status', ['submitted', 'under_review'])
        ->count();

    $documentGuide = [
        [
            'title' => 'Application and Undertaking Form',
            'document_type' => 'application_undertaking_form',
            'description' => 'The official adoption application and undertaking form for prospective adoptive parent applicants.',
            'required_for' => 'Usually required',
            'icon' => 'bi-file-earmark-text',
        ],
        [
            'title' => 'Birth Certificate of Applicant/s',
            'document_type' => 'applicant_birth_certificate',
            'description' => 'PSA/SECPA or authenticated birth certificate of the applicant or applicants.',
            'required_for' => 'Usually required',
            'icon' => 'bi-person-vcard',
        ],
        [
            'title' => 'Marriage Certificate / CENOMAR / Civil Status Document',
            'document_type' => 'civil_status_document',
            'description' => 'Marriage Certificate for married applicants, CENOMAR for single applicants, or another applicable civil-status document.',
            'required_for' => 'Based on civil status',
            'icon' => 'bi-heart',
        ],
        [
            'title' => 'Written Consent',
            'document_type' => 'written_consent',
            'description' => 'Written consent from the required person or persons, depending on the applicant and case circumstances.',
            'required_for' => 'When applicable',
            'icon' => 'bi-pen',
        ],
        [
            'title' => 'Medical Evaluation and Certification',
            'document_type' => 'medical_evaluation',
            'description' => 'Medical evaluation form, test results, and physician certification showing capacity to assume parental responsibilities.',
            'required_for' => 'Usually required',
            'icon' => 'bi-clipboard2-pulse',
        ],
        [
            'title' => 'Psychological Evaluation Report',
            'document_type' => 'psychological_evaluation_parent',
            'description' => 'Psychological evaluation report of the applicant or applicants when recommended by the social worker.',
            'required_for' => 'When recommended',
            'icon' => 'bi-activity',
        ],
        [
            'title' => 'NBI / Police / Court Clearance',
            'document_type' => 'clearance',
            'description' => 'Valid clearance showing no disqualifying criminal record, subject to the required validity period.',
            'required_for' => 'Usually required',
            'icon' => 'bi-shield-check',
        ],
        [
            'title' => 'Financial Capacity Document / Latest ITR',
            'document_type' => 'financial_capacity',
            'description' => 'Latest Income Tax Return, certificate of employment, bank certificate, business documents, or similar proof of financial capacity.',
            'required_for' => 'Usually required',
            'icon' => 'bi-cash-coin',
        ],
        [
            'title' => 'Character Reference Letters',
            'document_type' => 'character_reference_letters',
            'description' => 'Reference letters from non-related persons who can attest to the applicant’s character and suitability.',
            'required_for' => 'Usually required',
            'icon' => 'bi-people',
        ],
        [
            'title' => 'Recent 5R Photos',
            'document_type' => 'recent_5r_photos',
            'description' => 'Recent dated photos of the applicant or applicants, immediate family members, and home environment.',
            'required_for' => 'Usually required',
            'icon' => 'bi-images',
        ],
        [
            'title' => 'Certificate of Finality for Previous Adoption',
            'document_type' => 'previous_adoption_certificate_finality',
            'description' => 'Certificate of Finality or a similar document if the applicant has previously adopted a child.',
            'required_for' => 'If applicable',
            'icon' => 'bi-patch-check',
        ],
        [
            'title' => 'Pre-Adoption Forum / Training Certificate',
            'document_type' => 'pre_adoption_forum_certificate',
            'description' => 'Certificate of attendance, participation, completion, or undertaking for required adoption-related training.',
            'required_for' => 'Usually required',
            'icon' => 'bi-mortarboard',
        ],
        [
            'title' => 'Foreign National Residency / Police Clearance',
            'document_type' => 'foreign_national_requirements',
            'description' => 'Additional residency and police-clearance documents may be required for foreign-national applicants.',
            'required_for' => 'Foreign nationals',
            'icon' => 'bi-globe',
        ],
    ];

    $assignedDocumentTypes = $documents
        ->pluck('document_type')
        ->filter()
        ->values()
        ->toArray();
@endphp

<style>
    .docs-page {
        --docs-primary: #9f3f24;
        --docs-primary-dark: #7c2d19;
        --docs-primary-soft: #fff3ed;
        --docs-blue: #2563eb;
        --docs-green: #15803d;
        --docs-amber: #b45309;
        --docs-red: #b91c1c;
        --docs-purple: #7c3aed;
        --docs-ink: #172033;
        --docs-muted: #667085;
        --docs-line: #e5e9f0;
        --docs-surface: #ffffff;
        --docs-soft: #f7f9fc;
        display: grid;
        gap: 18px;
        color: var(--docs-ink);
    }

    .docs-page *,
    .docs-page *::before,
    .docs-page *::after {
        box-sizing: border-box;
    }

    .docs-page .visually-hidden {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    .docs-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
        padding: 26px;
        border: 1px solid #f0d7cd;
        border-radius: 24px;
        background:
            radial-gradient(circle at 88% 10%, rgba(159, 63, 36, 0.12), transparent 31%),
            linear-gradient(135deg, #fffaf7 0%, #ffffff 58%, #f8fafc 100%);
        box-shadow: 0 18px 42px rgba(20, 31, 51, 0.07);
    }

    .docs-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        padding: 7px 11px;
        border: 1px solid #f1c8b8;
        border-radius: 999px;
        background: var(--docs-primary-soft);
        color: var(--docs-primary-dark);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .docs-hero h1 {
        margin: 0;
        font-size: clamp(26px, 3vw, 38px);
        line-height: 1.15;
        color: #121827;
    }

    .docs-hero p {
        max-width: 740px;
        margin: 10px 0 0;
        color: var(--docs-muted);
        font-size: 15px;
        line-height: 1.7;
    }

    .docs-case-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .docs-case-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid var(--docs-line);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.86);
        color: #475467;
        font-size: 13px;
        font-weight: 800;
    }

    .docs-progress-ring {
        --progress: 0;
        position: relative;
        width: 132px;
        aspect-ratio: 1;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 50%;
        background:
            radial-gradient(closest-side, #ffffff 73%, transparent 74% 100%),
            conic-gradient(
                var(--docs-primary) calc(var(--progress) * 1%),
                #eceff4 0
            );
        box-shadow: 0 12px 28px rgba(159, 63, 36, 0.13);
    }

    .docs-progress-ring::after {
        content: "";
        position: absolute;
        inset: 11px;
        border: 1px solid #f2e4de;
        border-radius: 50%;
        pointer-events: none;
    }

    .docs-progress-ring-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .docs-progress-ring strong {
        display: block;
        font-size: 29px;
        line-height: 1;
        color: var(--docs-primary-dark);
    }

    .docs-progress-ring span {
        display: block;
        margin-top: 5px;
        color: var(--docs-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .docs-safety {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        padding: 16px 18px;
        border: 1px solid #fed7aa;
        border-radius: 18px;
        background: #fff8ed;
        color: #9a4f0c;
        line-height: 1.6;
    }

    .docs-safety-icon {
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

    .docs-safety strong {
        display: block;
        margin-bottom: 2px;
        color: #8a3e08;
    }

    .docs-alert {
        padding: 14px 16px;
        border: 1px solid #fecaca;
        border-radius: 15px;
        background: #fff1f2;
        color: #9f1239;
        line-height: 1.55;
    }

    .docs-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .docs-stat {
        min-width: 0;
        padding: 17px;
        border: 1px solid var(--docs-line);
        border-radius: 18px;
        background: var(--docs-surface);
        box-shadow: 0 10px 24px rgba(20, 31, 51, 0.045);
    }

    .docs-stat-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
    }

    .docs-stat-label {
        color: var(--docs-muted);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .docs-stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: #f1f5f9;
        color: var(--docs-blue);
        font-size: 18px;
    }

    .docs-stat-value {
        margin-top: 12px;
        font-size: 29px;
        font-weight: 950;
        line-height: 1;
        color: #101828;
    }

    .docs-stat-help {
        margin-top: 6px;
        color: var(--docs-muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .docs-stat.is-action .docs-stat-icon {
        background: #fff1f2;
        color: var(--docs-red);
    }

    .docs-stat.is-waiting .docs-stat-icon {
        background: #eff6ff;
        color: var(--docs-blue);
    }

    .docs-stat.is-verified .docs-stat-icon {
        background: #ecfdf3;
        color: var(--docs-green);
    }

    .docs-workspace,
    .docs-guide,
    .docs-empty {
        border: 1px solid var(--docs-line);
        border-radius: 22px;
        background: var(--docs-surface);
        box-shadow: 0 14px 34px rgba(20, 31, 51, 0.055);
    }

    .docs-workspace-header {
        display: grid;
        gap: 16px;
        padding: 21px;
        border-bottom: 1px solid var(--docs-line);
    }

    .docs-heading-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .docs-heading-row h2 {
        margin: 0;
        color: #101828;
        font-size: 22px;
    }

    .docs-heading-row p {
        margin: 6px 0 0;
        color: var(--docs-muted);
        line-height: 1.55;
    }

    .docs-count {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border: 1px solid var(--docs-line);
        border-radius: 999px;
        background: var(--docs-soft);
        color: #475467;
        font-size: 12px;
        font-weight: 850;
        white-space: nowrap;
    }

    .docs-toolbar {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 12px;
        align-items: center;
    }

    .docs-search {
        position: relative;
    }

    .docs-search i {
        position: absolute;
        top: 50%;
        left: 14px;
        z-index: 1;
        color: #98a2b3;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .docs-search input {
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

    .docs-search input:focus {
        border-color: var(--docs-primary);
        box-shadow: 0 0 0 4px rgba(159, 63, 36, 0.11);
    }

    .docs-search-clear {
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

    .docs-search-clear.is-visible {
        display: grid;
    }

    .docs-search-clear:hover {
        background: #f2f4f7;
    }

    .docs-filter-row {
        display: flex;
        gap: 7px;
        align-items: center;
        flex-wrap: wrap;
    }

    .docs-filter {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        background: #ffffff;
        color: #475467;
        font-size: 13px;
        font-weight: 850;
        cursor: pointer;
        transition: 0.16s ease;
    }

    .docs-filter:hover {
        border-color: #b7bec9;
        background: #f9fafb;
    }

    .docs-filter.is-active {
        border-color: var(--docs-primary);
        background: var(--docs-primary-soft);
        color: var(--docs-primary-dark);
        box-shadow: 0 0 0 3px rgba(159, 63, 36, 0.08);
    }

    .docs-list {
        display: grid;
    }

    .docs-card {
        position: relative;
        border-bottom: 1px solid var(--docs-line);
        background: #ffffff;
    }

    .docs-card:last-child {
        border-bottom: none;
        border-radius: 0 0 22px 22px;
    }

    .docs-card[hidden] {
        display: none !important;
    }

    .docs-card-main {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 17px 20px;
        transition: 0.16s ease;
    }

    .docs-card:hover .docs-card-main {
        background: #fbfcfe;
    }

    .docs-file-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border: 1px solid #e4e7ec;
        border-radius: 14px;
        background: #f8fafc;
        color: #475467;
        font-size: 20px;
    }

    .docs-card.is-pending .docs-file-icon {
        background: #fffbeb;
        color: var(--docs-amber);
    }

    .docs-card.is-submitted .docs-file-icon,
    .docs-card.is-review .docs-file-icon {
        background: #eff6ff;
        color: var(--docs-blue);
    }

    .docs-card.is-verified .docs-file-icon {
        background: #ecfdf3;
        color: var(--docs-green);
    }

    .docs-card.is-rejected .docs-file-icon,
    .docs-card.is-expired .docs-file-icon {
        background: #fff1f2;
        color: var(--docs-red);
    }

    .docs-card-copy {
        min-width: 0;
    }

    .docs-card-title-row {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .docs-card-title {
        margin: 0;
        color: #101828;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.35;
    }

    .docs-card-subtitle {
        display: flex;
        gap: 8px 14px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 6px;
        color: var(--docs-muted);
        font-size: 12px;
    }

    .docs-card-subtitle span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
    }

    .docs-filename {
        max-width: 420px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .docs-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }

    .docs-status.is-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .docs-status.is-submitted {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .docs-status.is-review {
        background: #ede9fe;
        color: #6d28d9;
    }

    .docs-status.is-verified {
        background: #dcfce7;
        color: #166534;
    }

    .docs-status.is-rejected,
    .docs-status.is-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    .docs-card-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .docs-btn {
        min-height: 39px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid #d0d5dd;
        border-radius: 11px;
        background: #ffffff;
        color: #344054;
        font-size: 12px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        transition: 0.16s ease;
    }

    .docs-btn:hover {
        border-color: #b7bec9;
        background: #f9fafb;
        color: #101828;
    }

    .docs-btn.is-primary {
        border-color: var(--docs-primary);
        background: var(--docs-primary);
        color: #ffffff;
    }

    .docs-btn.is-primary:hover {
        border-color: var(--docs-primary-dark);
        background: var(--docs-primary-dark);
        color: #ffffff;
    }

    .docs-btn.is-danger-soft {
        border-color: #fecaca;
        background: #fff1f2;
        color: #9f1239;
    }

    .docs-btn:focus-visible,
    .docs-filter:focus-visible,
    .docs-upload-close:focus-visible,
    .docs-dropzone:focus-visible,
    .docs-guide summary:focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.24);
        outline-offset: 2px;
    }

    .docs-card-details {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        padding: 0 20px 18px 80px;
    }

    .docs-card-details[hidden] {
        display: none;
    }

    .docs-detail-box {
        min-width: 0;
        padding: 13px;
        border: 1px solid var(--docs-line);
        border-radius: 13px;
        background: var(--docs-soft);
    }

    .docs-detail-label {
        display: block;
        margin-bottom: 5px;
        color: #667085;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .docs-detail-value {
        color: #344054;
        font-size: 13px;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .docs-detail-box.is-wide {
        grid-column: span 3;
    }

    .docs-no-results {
        display: none;
        padding: 42px 20px;
        text-align: center;
        color: var(--docs-muted);
    }

    .docs-no-results.is-visible {
        display: block;
    }

    .docs-no-results-icon,
    .docs-empty-icon {
        width: 62px;
        height: 62px;
        display: grid;
        place-items: center;
        margin: 0 auto 13px;
        border-radius: 20px;
        background: #f2f4f7;
        color: #667085;
        font-size: 27px;
    }

    .docs-no-results h3,
    .docs-empty h2 {
        margin: 0;
        color: #101828;
    }

    .docs-no-results p,
    .docs-empty p {
        margin: 7px auto 0;
        max-width: 620px;
        line-height: 1.65;
    }

    .docs-guide {
        overflow: hidden;
    }

    .docs-guide summary {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        padding: 19px 21px;
        list-style: none;
        cursor: pointer;
        user-select: none;
    }

    .docs-guide summary::-webkit-details-marker {
        display: none;
    }

    .docs-guide-title {
        display: flex;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .docs-guide-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 14px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 19px;
    }

    .docs-guide-title strong {
        display: block;
        color: #101828;
        font-size: 16px;
    }

    .docs-guide-title span {
        display: block;
        margin-top: 3px;
        color: var(--docs-muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .docs-guide-chevron {
        color: #667085;
        transition: transform 0.2s ease;
    }

    .docs-guide[open] .docs-guide-chevron {
        transform: rotate(180deg);
    }

    .docs-guide-content {
        padding: 0 21px 21px;
        border-top: 1px solid var(--docs-line);
    }

    .docs-guide-note {
        margin: 17px 0;
        padding: 13px 15px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.55;
    }

    .docs-guide-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 11px;
    }

    .docs-guide-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 11px;
        padding: 14px;
        border: 1px solid var(--docs-line);
        border-radius: 15px;
        background: #ffffff;
    }

    .docs-guide-card-icon {
        width: 39px;
        height: 39px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #f5f3ff;
        color: #6d28d9;
    }

    .docs-guide-card-title-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: flex-start;
    }

    .docs-guide-card strong {
        color: #344054;
        font-size: 13px;
        line-height: 1.4;
    }

    .docs-guide-card p {
        margin: 6px 0 9px;
        color: var(--docs-muted);
        font-size: 12px;
        line-height: 1.55;
    }

    .docs-guide-badge {
        display: inline-flex;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f2f4f7;
        color: #475467;
        font-size: 10px;
        font-weight: 900;
        white-space: nowrap;
    }

    .docs-guide-badge.is-assigned {
        background: #dcfce7;
        color: #166534;
    }

    .docs-guide-requirement {
        display: inline-flex;
        padding: 5px 8px;
        border: 1px solid var(--docs-line);
        border-radius: 999px;
        color: #475467;
        font-size: 10px;
        font-weight: 850;
    }

    .docs-empty {
        padding: 44px 24px;
        text-align: center;
        color: var(--docs-muted);
    }

    .docs-empty-actions {
        display: flex;
        justify-content: center;
        gap: 9px;
        margin-top: 18px;
        flex-wrap: wrap;
    }

    .docs-upload-modal[hidden] {
        display: none !important;
    }

    .docs-upload-modal {
        --docs-primary: #9f3f24;
        --docs-primary-dark: #7c2d19;
        --docs-primary-soft: #fff3ed;
        --docs-red: #b91c1c;
        --docs-muted: #667085;
        --docs-line: #e5e9f0;
        --docs-soft: #f7f9fc;
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: grid;
        place-items: center;
        padding: 20px;
    }

    .docs-upload-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(5px);
    }

    .docs-upload-dialog {
        position: relative;
        z-index: 1;
        width: min(100%, 620px);
        max-height: min(88vh, 760px);
        overflow-y: auto;
        border: 1px solid #e4e7ec;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.27);
    }

    .docs-upload-header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding: 20px 21px 16px;
        border-bottom: 1px solid var(--docs-line);
    }

    .docs-upload-header h2 {
        margin: 0;
        color: #101828;
        font-size: 20px;
    }

    .docs-upload-header p {
        margin: 5px 0 0;
        color: var(--docs-muted);
        line-height: 1.5;
    }

    .docs-upload-close {
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

    .docs-upload-close:hover {
        background: #f9fafb;
    }

    .docs-upload-body {
        display: grid;
        gap: 15px;
        padding: 20px 21px;
    }

    .docs-upload-guidance {
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

    .docs-dropzone {
        position: relative;
        display: grid;
        place-items: center;
        min-height: 190px;
        padding: 24px;
        border: 2px dashed #cbd5e1;
        border-radius: 18px;
        background: #fbfcfe;
        text-align: center;
        cursor: pointer;
        transition: 0.18s ease;
    }

    .docs-dropzone:hover,
    .docs-dropzone.is-dragover {
        border-color: var(--docs-primary);
        background: var(--docs-primary-soft);
    }

    .docs-dropzone.is-invalid {
        border-color: var(--docs-red);
        background: #fff1f2;
    }

    .docs-dropzone input {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }

    .docs-dropzone-icon {
        width: 55px;
        height: 55px;
        display: grid;
        place-items: center;
        margin: 0 auto 11px;
        border-radius: 18px;
        background: var(--docs-primary-soft);
        color: var(--docs-primary);
        font-size: 24px;
    }

    .docs-dropzone strong {
        display: block;
        color: #101828;
    }

    .docs-dropzone span {
        display: block;
        margin-top: 5px;
        color: var(--docs-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .docs-file-preview {
        display: none;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 11px;
        align-items: center;
        padding: 12px;
        border: 1px solid var(--docs-line);
        border-radius: 14px;
        background: var(--docs-soft);
    }

    .docs-file-preview.is-visible {
        display: grid;
    }

    .docs-file-preview-icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #ffffff;
        color: var(--docs-primary);
        font-size: 19px;
    }

    .docs-file-preview-copy {
        min-width: 0;
    }

    .docs-file-preview-name {
        overflow: hidden;
        color: #344054;
        font-size: 13px;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .docs-file-preview-size {
        margin-top: 3px;
        color: var(--docs-muted);
        font-size: 11px;
    }

    .docs-file-remove {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fff1f2;
        color: #9f1239;
        cursor: pointer;
    }

    .docs-upload-error {
        display: none;
        color: #b42318;
        font-size: 12px;
        line-height: 1.45;
    }

    .docs-upload-error.is-visible {
        display: block;
    }

    .docs-upload-footer {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 16px 21px 20px;
        border-top: 1px solid var(--docs-line);
    }

    .docs-upload-submit:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    body.docs-modal-open {
        overflow: hidden;
    }

    @media (max-width: 1080px) {
        .docs-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .docs-toolbar {
            grid-template-columns: 1fr;
        }

        .docs-card-main {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .docs-card-actions {
            grid-column: 2;
            justify-content: flex-start;
        }

        .docs-card-details {
            padding-left: 80px;
        }
    }

    @media (max-width: 760px) {
        .docs-page {
            gap: 14px;
        }

        .docs-hero {
            grid-template-columns: 1fr;
            padding: 20px;
        }

        .docs-progress-ring {
            width: 116px;
        }

        .docs-stats,
        .docs-guide-grid {
            grid-template-columns: 1fr;
        }

        .docs-filter-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .docs-filter {
            width: 100%;
        }

        .docs-card-main {
            grid-template-columns: auto minmax(0, 1fr);
            padding: 15px;
        }

        .docs-card-actions {
            grid-column: 1 / -1;
            justify-content: stretch;
        }

        .docs-card-actions .docs-btn {
            flex: 1 1 120px;
        }

        .docs-card-details {
            grid-template-columns: 1fr;
            padding: 0 15px 15px;
        }

        .docs-detail-box.is-wide {
            grid-column: auto;
        }

        .docs-guide summary,
        .docs-guide-content,
        .docs-workspace-header {
            padding-left: 16px;
            padding-right: 16px;
        }

        .docs-upload-modal {
            align-items: end;
            padding: 0;
        }

        .docs-upload-dialog {
            width: 100%;
            max-height: 92vh;
            border-radius: 22px 22px 0 0;
        }
    }

    @media (max-width: 480px) {
        .docs-filter-row {
            grid-template-columns: 1fr;
        }

        .docs-heading-row {
            display: grid;
        }

        .docs-count {
            justify-self: start;
        }

        .docs-file-icon {
            width: 42px;
            height: 42px;
        }

        .docs-filename {
            max-width: 230px;
        }

        .docs-guide-card-title-row {
            display: grid;
        }

        .docs-guide-badge {
            justify-self: start;
        }

        .docs-upload-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .docs-page *,
        .docs-page *::before,
        .docs-page *::after {
            scroll-behavior: auto !important;
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<div class="docs-page">
    <section class="docs-hero">
        <div>
            <div class="docs-eyebrow">
                <i class="bi bi-folder2-open"></i>
                Parent document center
            </div>

            <h1>Manage your required documents</h1>

            <p>
                Review your official checklist, see what needs attention, and upload one document at a time
                without scrolling through multiple file forms.
            </p>

            <div class="docs-case-row">
                <span class="docs-case-pill">
                    <i class="bi bi-person"></i>
                    {{ $user->name }}
                </span>

                @if($adoptionCase)
                    <span class="docs-case-pill">
                        <i class="bi bi-folder-check"></i>
                        Case {{ $adoptionCase->case_code }}
                    </span>

                    <span class="docs-case-pill">
                        <i class="bi bi-shield-check"></i>
                        {{ $verifiedDocumentsCount }} verified
                    </span>
                @else
                    <span class="docs-case-pill">
                        <i class="bi bi-hourglass-split"></i>
                        Awaiting case assignment
                    </span>
                @endif
            </div>
        </div>

        <div
            class="docs-progress-ring"
            style="--progress: {{ $safeProgress }};"
            role="img"
            aria-label="{{ $safeProgress }} percent of required documents verified"
        >
            <div class="docs-progress-ring-content">
                <strong>{{ $safeProgress }}%</strong>
                <span>Verified</span>
            </div>
        </div>
    </section>

    <section class="docs-safety" aria-label="Document privacy reminder">
        <div class="docs-safety-icon">
            <i class="bi bi-shield-lock"></i>
        </div>

        <div>
            <strong>Upload only requested parent-side documents.</strong>
            Do not upload child records, donor records, passwords, unrelated files, or information that
            authorized AmoraCare staff did not request.
        </div>
    </section>

    @if($errors->has('file'))
        <div class="docs-alert" role="alert">
            <strong>Upload unsuccessful.</strong>
            {{ $errors->first('file') }}
        </div>
    @endif

    @if($adoptionCase)
        <section class="docs-stats" aria-label="Document overview">
            <article class="docs-stat">
                <div class="docs-stat-top">
                    <div class="docs-stat-label">Required</div>
                    <div class="docs-stat-icon">
                        <i class="bi bi-files"></i>
                    </div>
                </div>
                <div class="docs-stat-value">{{ $requiredDocumentsCount }}</div>
                <div class="docs-stat-help">Documents assigned to your official checklist</div>
            </article>

            <article class="docs-stat is-action">
                <div class="docs-stat-top">
                    <div class="docs-stat-label">Needs action</div>
                    <div class="docs-stat-icon">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                </div>
                <div class="docs-stat-value">{{ $needsActionCount }}</div>
                <div class="docs-stat-help">Pending, rejected, or expired documents</div>
            </article>

            <article class="docs-stat is-waiting">
                <div class="docs-stat-top">
                    <div class="docs-stat-label">In review</div>
                    <div class="docs-stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <div class="docs-stat-value">{{ $waitingCount }}</div>
                <div class="docs-stat-help">Submitted files waiting for staff action</div>
            </article>

            <article class="docs-stat is-verified">
                <div class="docs-stat-top">
                    <div class="docs-stat-label">Verified</div>
                    <div class="docs-stat-icon">
                        <i class="bi bi-patch-check"></i>
                    </div>
                </div>
                <div class="docs-stat-value">{{ $verifiedDocumentsCount }}</div>
                <div class="docs-stat-help">Accepted files; replacements require another review</div>
            </article>
        </section>

        <section class="docs-workspace">
            <header class="docs-workspace-header">
                <div class="docs-heading-row">
                    <div>
                        <h2>Official document checklist</h2>
                        <p>
                            Search or filter your checklist. You may replace an uploaded file before final RACCO
                            case approval; the replacement returns to <strong>Submitted</strong> status.
                        </p>
                    </div>

                    <div class="docs-count" aria-live="polite">
                        <i class="bi bi-list-check"></i>
                        <span id="docsVisibleCount">{{ $documents->count() }}</span>
                        of {{ $documents->count() }} shown
                    </div>
                </div>

                <div class="docs-toolbar">
                    <div class="docs-search">
                        <i class="bi bi-search"></i>

                        <label for="docsSearch" class="visually-hidden">
                            Search required documents
                        </label>

                        <input
                            type="search"
                            id="docsSearch"
                            placeholder="Search document name, file, status, or remarks..."
                            autocomplete="off"
                        >

                        <button
                            type="button"
                            class="docs-search-clear"
                            id="docsSearchClear"
                            aria-label="Clear document search"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="docs-filter-row" role="group" aria-label="Filter documents by status">
                        <button type="button" class="docs-filter is-active" data-filter="all">
                            <i class="bi bi-grid"></i>
                            All
                        </button>

                        <button type="button" class="docs-filter" data-filter="action">
                            <i class="bi bi-exclamation-circle"></i>
                            Needs action
                        </button>

                        <button type="button" class="docs-filter" data-filter="waiting">
                            <i class="bi bi-hourglass-split"></i>
                            In review
                        </button>

                        <button type="button" class="docs-filter" data-filter="verified">
                            <i class="bi bi-patch-check"></i>
                            Verified
                        </button>
                    </div>
                </div>
            </header>

            @if($documents->count())
                <div class="docs-list" id="docsList">
                    @foreach($documents as $document)
                        @php
                            $meta = $statusMeta[$document->status] ?? [
                                'label' => $document->status_label ?? ucwords(str_replace('_', ' ', (string) $document->status)),
                                'icon' => 'bi-info-circle',
                                'class' => 'is-pending',
                                'description' => 'Review this document status with authorized AmoraCare staff.',
                            ];

                            $needsAction = in_array($document->status, $needsActionStatuses, true);
                            $waiting = in_array($document->status, ['submitted', 'under_review'], true);

                            $searchText = strtolower(implode(' ', array_filter([
                                $document->document_name,
                                $document->document_type,
                                $document->scope_label ?? null,
                                $document->status,
                                $document->status_label ?? null,
                                $document->original_filename,
                                $document->remarks,
                            ])));
                        @endphp

                        <article
                            class="docs-card {{ $meta['class'] }}"
                            data-doc-card
                            data-search="{{ $searchText }}"
                            data-status="{{ $document->status }}"
                            data-action="{{ $needsAction ? '1' : '0' }}"
                            data-waiting="{{ $waiting ? '1' : '0' }}"
                        >
                            <div class="docs-card-main">
                                <div class="docs-file-icon" aria-hidden="true">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                </div>

                                <div class="docs-card-copy">
                                    <div class="docs-card-title-row">
                                        <h3 class="docs-card-title">
                                            {{ $document->document_name }}
                                        </h3>

                                        <span class="docs-status {{ $meta['class'] }}">
                                            <i class="bi {{ $meta['icon'] }}"></i>
                                            {{ $meta['label'] }}
                                        </span>
                                    </div>

                                    <div class="docs-card-subtitle">
                                        <span>
                                            <i class="bi bi-tag"></i>
                                            {{ $document->scope_label ?? 'Parent requirement' }}
                                        </span>

                                        @if($document->original_filename)
                                            <span
                                                class="docs-filename"
                                                title="{{ $document->original_filename }}"
                                            >
                                                <i class="bi bi-paperclip"></i>
                                                {{ $document->original_filename }}
                                            </span>
                                        @else
                                            <span>
                                                <i class="bi bi-cloud-slash"></i>
                                                No file uploaded
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="docs-card-actions">
                                    <button
                                        type="button"
                                        class="docs-btn"
                                        data-details-toggle="{{ $document->id }}"
                                        aria-expanded="false"
                                        aria-controls="docsDetails{{ $document->id }}"
                                    >
                                        <i class="bi bi-info-circle"></i>
                                        <span data-details-label>Details</span>
                                    </button>

                                    @if($document->file_path)
                                        <a
                                            href="{{ route('parent.documents.download', $document) }}"
                                            class="docs-btn"
                                        >
                                            <i class="bi bi-download"></i>
                                            Download
                                        </a>
                                    @endif

                                    @if(! $documentUploadsLocked && $document->status !== 'not_required')
                                        <button
                                            type="button"
                                            class="docs-btn {{ $needsAction ? 'is-primary' : '' }}"
                                            data-upload-open
                                            data-upload-action="{{ route('parent.documents.upload', $document) }}"
                                            data-document-name="{{ $document->document_name }}"
                                            data-has-file="{{ $document->file_path ? '1' : '0' }}"
                                        >
                                            <i class="bi {{ $document->file_path ? 'bi-arrow-repeat' : 'bi-cloud-arrow-up' }}"></i>
                                            {{ $document->file_path ? 'Replace' : 'Upload' }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div
                                class="docs-card-details"
                                id="docsDetails{{ $document->id }}"
                                hidden
                            >
                                <div class="docs-detail-box">
                                    <span class="docs-detail-label">Current status</span>
                                    <div class="docs-detail-value">
                                        {{ $meta['description'] }}
                                    </div>
                                </div>

                                <div class="docs-detail-box">
                                    <span class="docs-detail-label">Expiry date</span>
                                    <div class="docs-detail-value">
                                        {{ $document->expiry_date?->format('M d, Y') ?? 'No expiry date recorded' }}
                                    </div>
                                </div>

                                <div class="docs-detail-box">
                                    <span class="docs-detail-label">Verified date</span>
                                    <div class="docs-detail-value">
                                        {{ $document->verified_at?->format('M d, Y h:i A') ?? 'Not yet verified' }}
                                    </div>
                                </div>

                                <div class="docs-detail-box is-wide">
                                    <span class="docs-detail-label">Staff remarks</span>
                                    <div class="docs-detail-value">
                                        {{ $document->remarks ?: 'No remarks have been added for this document.' }}
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="docs-no-results" id="docsNoResults">
                    <div class="docs-no-results-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3>No matching documents</h3>
                    <p>Try another search term or choose a different status filter.</p>
                </div>
            @else
                <div class="docs-no-results is-visible">
                    <div class="docs-no-results-icon">
                        <i class="bi bi-file-earmark"></i>
                    </div>
                    <h3>No document checklist assigned</h3>
                    <p>Authorized AmoraCare staff have not assigned parent-side requirements yet.</p>
                </div>
            @endif
        </section>

        <details class="docs-guide">
            <summary>
                <div class="docs-guide-title">
                    <div class="docs-guide-icon">
                        <i class="bi bi-journal-check"></i>
                    </div>

                    <div>
                        <strong>Preparation guide for common adoption documents</strong>
                        <span>
                            Optional reference only. Your official checklist above remains the source of truth.
                        </span>
                    </div>
                </div>

                <i class="bi bi-chevron-down docs-guide-chevron" aria-hidden="true"></i>
            </summary>

            <div class="docs-guide-content">
                <div class="docs-guide-note">
                    <i class="bi bi-info-circle"></i>
                    Prepare or upload an item only when it appears in your official checklist or when your
                    assigned social worker or authorized AmoraCare staff specifically requests it.
                </div>

                <div class="docs-guide-grid">
                    @foreach($documentGuide as $item)
                        @php
                            $isAssigned = in_array(
                                $item['document_type'],
                                $assignedDocumentTypes,
                                true
                            );
                        @endphp

                        <article class="docs-guide-card">
                            <div class="docs-guide-card-icon">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </div>

                            <div>
                                <div class="docs-guide-card-title-row">
                                    <strong>{{ $item['title'] }}</strong>

                                    <span class="docs-guide-badge {{ $isAssigned ? 'is-assigned' : '' }}">
                                        {{ $isAssigned ? 'In checklist' : 'Guide only' }}
                                    </span>
                                </div>

                                <p>{{ $item['description'] }}</p>

                                <span class="docs-guide-requirement">
                                    {{ $item['required_for'] }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </details>
    @else
        <section class="docs-empty">
            <div class="docs-empty-icon">
                <i class="bi bi-folder-x"></i>
            </div>

            <h2>No active adoption case yet</h2>

            <p>
                Your account is active, but an adoption case has not yet been assigned. Your required
                documents will appear here once authorized AmoraCare staff creates your case.
            </p>

            <div class="docs-empty-actions">
                <a href="{{ route('parent.dashboard') }}" class="docs-btn is-primary">
                    <i class="bi bi-grid"></i>
                    Return to dashboard
                </a>

                <a href="{{ route('parent.ai.index') }}" class="docs-btn">
                    <i class="bi bi-chat-square-text"></i>
                    Ask AmoraCare Guide
                </a>
            </div>
        </section>
    @endif
</div>

@if($adoptionCase && $documents->count())
    <div
        class="docs-upload-modal"
        id="docsUploadModal"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="docsUploadTitle"
    >
        <button
            type="button"
            class="docs-upload-backdrop"
            data-upload-close
            aria-label="Close upload dialog"
        ></button>

        <div class="docs-upload-dialog">
            <form
                id="docsUploadForm"
                method="POST"
                enctype="multipart/form-data"
                action=""
            >
                @csrf

                <header class="docs-upload-header">
                    <div>
                        <h2 id="docsUploadTitle">Upload document</h2>
                        <p id="docsUploadDocumentName">
                            Select a document from your checklist.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="docs-upload-close"
                        data-upload-close
                        aria-label="Close upload dialog"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>

                <div class="docs-upload-body">
                    <div class="docs-upload-guidance">
                        <i class="bi bi-shield-check"></i>
                        <span>
                            Accepted formats: PDF, JPG, JPEG, PNG, DOC, and DOCX. Maximum size: 5 MB.
                            Confirm that the file is correct and contains only the requested information.
                        </span>
                    </div>

                    <label
                        for="docsUploadFile"
                        class="docs-dropzone"
                        id="docsDropzone"
                        tabindex="0"
                    >
                        <input
                            type="file"
                            name="file"
                            id="docsUploadFile"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            required
                        >

                        <span>
                            <span class="docs-dropzone-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </span>

                            <strong>Drop your file here or click to browse</strong>
                            <span>One file only, up to 5 MB</span>
                        </span>
                    </label>

                    <div class="docs-file-preview" id="docsFilePreview">
                        <div class="docs-file-preview-icon">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>

                        <div class="docs-file-preview-copy">
                            <div class="docs-file-preview-name" id="docsFileName"></div>
                            <div class="docs-file-preview-size" id="docsFileSize"></div>
                        </div>

                        <button
                            type="button"
                            class="docs-file-remove"
                            id="docsFileRemove"
                            aria-label="Remove selected file"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div
                        class="docs-upload-error"
                        id="docsUploadError"
                        role="alert"
                    ></div>
                </div>

                <footer class="docs-upload-footer">
                    <button type="button" class="docs-btn" data-upload-close>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="docs-btn is-primary docs-upload-submit"
                        id="docsUploadSubmit"
                        disabled
                    >
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span id="docsUploadSubmitText">Upload file</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
@endif

<script>
document.addEventListener("DOMContentLoaded", function () {
    const cards = Array.from(document.querySelectorAll("[data-doc-card]"));
    const searchInput = document.getElementById("docsSearch");
    const searchClear = document.getElementById("docsSearchClear");
    const filterButtons = Array.from(document.querySelectorAll("[data-filter]"));
    const visibleCount = document.getElementById("docsVisibleCount");
    const noResults = document.getElementById("docsNoResults");

    let activeFilter = "all";

    function normalizeText(value) {
        return String(value || "")
            .toLowerCase()
            .replace(/\s+/g, " ")
            .trim();
    }

    function cardMatchesFilter(card) {
        if (activeFilter === "all") {
            return true;
        }

        if (activeFilter === "action") {
            return card.dataset.action === "1";
        }

        if (activeFilter === "waiting") {
            return card.dataset.waiting === "1";
        }

        if (activeFilter === "verified") {
            return card.dataset.status === "verified";
        }

        return true;
    }

    function applyFilters() {
        if (!cards.length) {
            return;
        }

        const term = normalizeText(searchInput?.value);
        let count = 0;

        cards.forEach((card) => {
            const searchable = normalizeText(card.dataset.search);
            const matchesSearch = !term || searchable.includes(term);
            const matchesFilter = cardMatchesFilter(card);
            const isVisible = matchesSearch && matchesFilter;

            card.hidden = !isVisible;

            if (isVisible) {
                count += 1;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = String(count);
        }

        if (noResults) {
            noResults.classList.toggle("is-visible", count === 0);
        }

        if (searchClear) {
            searchClear.classList.toggle(
                "is-visible",
                Boolean(searchInput?.value)
            );
        }
    }

    filterButtons.forEach((button) => {
        button.addEventListener("click", function () {
            activeFilter = button.dataset.filter || "all";

            filterButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-pressed", isActive ? "true" : "false");
            });

            applyFilters();
        });
    });

    searchInput?.addEventListener("input", applyFilters);

    searchClear?.addEventListener("click", function () {
        if (!searchInput) {
            return;
        }

        searchInput.value = "";
        searchInput.focus();
        applyFilters();
    });

    document.querySelectorAll("[data-details-toggle]").forEach((button) => {
        button.addEventListener("click", function () {
            const id = button.dataset.detailsToggle;
            const details = document.getElementById("docsDetails" + id);

            if (!details) {
                return;
            }

            const willOpen = details.hidden;
            details.hidden = !willOpen;
            button.setAttribute("aria-expanded", willOpen ? "true" : "false");

            const icon = button.querySelector("i");

            if (icon) {
                icon.className = willOpen
                    ? "bi bi-chevron-up"
                    : "bi bi-info-circle";
            }

            const label = button.querySelector("[data-details-label]");

            if (label) {
                label.textContent = willOpen ? "Hide details" : "Details";
            }
        });
    });

    const modal = document.getElementById("docsUploadModal");
    const uploadForm = document.getElementById("docsUploadForm");
    const uploadTitle = document.getElementById("docsUploadTitle");
    const uploadDocumentName = document.getElementById("docsUploadDocumentName");
    const fileInput = document.getElementById("docsUploadFile");
    const dropzone = document.getElementById("docsDropzone");
    const filePreview = document.getElementById("docsFilePreview");
    const fileName = document.getElementById("docsFileName");
    const fileSize = document.getElementById("docsFileSize");
    const fileRemove = document.getElementById("docsFileRemove");
    const uploadError = document.getElementById("docsUploadError");
    const uploadSubmit = document.getElementById("docsUploadSubmit");
    const uploadSubmitText = document.getElementById("docsUploadSubmitText");

    const maxFileSize = 5 * 1024 * 1024;
    const allowedExtensions = [
        "pdf",
        "jpg",
        "jpeg",
        "png",
        "doc",
        "docx"
    ];

    let lastFocusedElement = null;

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return "0 bytes";
        }

        const units = ["bytes", "KB", "MB", "GB"];
        const index = Math.min(
            Math.floor(Math.log(bytes) / Math.log(1024)),
            units.length - 1
        );
        const value = bytes / Math.pow(1024, index);

        return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
    }

    function setUploadError(message) {
        if (!uploadError || !dropzone) {
            return;
        }

        uploadError.textContent = message || "";
        uploadError.classList.toggle("is-visible", Boolean(message));
        dropzone.classList.toggle("is-invalid", Boolean(message));
    }

    function resetSelectedFile() {
        if (fileInput) {
            fileInput.value = "";
        }

        filePreview?.classList.remove("is-visible");

        if (fileName) {
            fileName.textContent = "";
        }

        if (fileSize) {
            fileSize.textContent = "";
        }

        if (uploadSubmit) {
            uploadSubmit.disabled = true;
        }

        setUploadError("");
    }

    function validateFile(file) {
        if (!file) {
            return "Select a file to continue.";
        }

        const extension = file.name.includes(".")
            ? file.name.split(".").pop().toLowerCase()
            : "";

        if (!allowedExtensions.includes(extension)) {
            return "Unsupported file type. Use PDF, JPG, JPEG, PNG, DOC, or DOCX.";
        }

        if (file.size > maxFileSize) {
            return "The selected file exceeds the 5 MB size limit.";
        }

        return "";
    }

    function presentFile(file) {
        const error = validateFile(file);

        if (error) {
            resetSelectedFile();
            setUploadError(error);
            return;
        }

        if (fileName) {
            fileName.textContent = file.name;
        }

        if (fileSize) {
            fileSize.textContent = formatBytes(file.size);
        }

        filePreview?.classList.add("is-visible");

        if (uploadSubmit) {
            uploadSubmit.disabled = false;
        }

        setUploadError("");
    }

    function assignDroppedFile(file) {
        if (!fileInput || !file) {
            return;
        }

        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            presentFile(file);
        } catch (error) {
            setUploadError(
                "Your browser could not attach the dropped file. Click the upload area to browse instead."
            );
        }
    }

    function openUploadModal(button) {
        if (!modal || !uploadForm) {
            return;
        }

        lastFocusedElement = document.activeElement;
        resetSelectedFile();

        uploadForm.action = button.dataset.uploadAction || "";

        const documentName =
            button.dataset.documentName || "Selected document";

        const hasExistingFile = button.dataset.hasFile === "1";

        if (uploadTitle) {
            uploadTitle.textContent = hasExistingFile
                ? "Replace document"
                : "Upload document";
        }

        if (uploadDocumentName) {
            uploadDocumentName.textContent = documentName;
        }

        if (uploadSubmitText) {
            uploadSubmitText.textContent = hasExistingFile
                ? "Replace file"
                : "Upload file";
        }

        modal.hidden = false;
        document.body.classList.add("docs-modal-open");

        window.setTimeout(() => {
            dropzone?.focus();
        }, 50);
    }

    function closeUploadModal() {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove("docs-modal-open");
        resetSelectedFile();

        if (uploadForm) {
            uploadForm.action = "";
        }

        lastFocusedElement?.focus?.();
    }

    document.querySelectorAll("[data-upload-open]").forEach((button) => {
        button.addEventListener("click", function () {
            openUploadModal(button);
        });
    });

    document.querySelectorAll("[data-upload-close]").forEach((button) => {
        button.addEventListener("click", closeUploadModal);
    });

    fileInput?.addEventListener("change", function () {
        presentFile(fileInput.files?.[0]);
    });

    fileRemove?.addEventListener("click", function () {
        resetSelectedFile();
        dropzone?.focus();
    });

    dropzone?.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            fileInput?.click();
        }
    });

    ["dragenter", "dragover"].forEach((eventName) => {
        dropzone?.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add("is-dragover");
        });
    });

    ["dragleave", "drop"].forEach((eventName) => {
        dropzone?.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove("is-dragover");
        });
    });

    dropzone?.addEventListener("drop", function (event) {
        const file = event.dataTransfer?.files?.[0];

        if (file) {
            assignDroppedFile(file);
        }
    });

    uploadForm?.addEventListener("submit", function (event) {
        const file = fileInput?.files?.[0];
        const error = validateFile(file);

        if (error) {
            event.preventDefault();
            setUploadError(error);
            return;
        }

        if (uploadSubmit) {
            uploadSubmit.disabled = true;
        }

        if (uploadSubmitText) {
            uploadSubmitText.textContent = "Uploading...";
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal && !modal.hidden) {
            closeUploadModal();
        }
    });

    applyFilters();
});
</script>
@endsection
