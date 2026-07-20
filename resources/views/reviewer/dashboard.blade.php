@extends('layouts.dashboard', ['title' => 'External Reviewer Dashboard'])

@section('content')
<style>
    .reviewer-hero {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
        border: 1px solid #dbeafe;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .reviewer-hero-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .reviewer-eyebrow {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 10px;
    }

    .reviewer-title {
        margin: 0;
        color: #0f172a;
        font-size: 28px;
        font-weight: 900;
    }

    .reviewer-subtitle {
        margin: 8px 0 0;
        color: #475569;
        line-height: 1.6;
        max-width: 780px;
    }

    .reviewer-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .reviewer-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
    }

    .reviewer-btn-primary {
        background: #2563eb;
        color: #ffffff;
    }

    .reviewer-btn-light {
        background: #ffffff;
        color: #334155;
        border-color: #dbe3ef;
    }

    .reviewer-notice {
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
        border-radius: 16px;
        padding: 16px;
        line-height: 1.6;
        margin-bottom: 18px;
        font-weight: 700;
    }

    .reviewer-cards {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .reviewer-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
    }

    .reviewer-card-title {
        color: #64748b;
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 8px;
    }

    .reviewer-card-value {
        color: #0f172a;
        font-size: 30px;
        font-weight: 900;
    }

    .reviewer-panel {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
        margin-bottom: 18px;
    }

    .reviewer-panel h2 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 20px;
        font-weight: 900;
    }

    .reviewer-panel p {
        margin: 0;
        color: #64748b;
        line-height: 1.6;
    }

    @media (max-width: 1000px) {
        .reviewer-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .reviewer-cards {
            grid-template-columns: 1fr;
        }

        .reviewer-title {
            font-size: 24px;
        }
    }
</style>

<div class="reviewer-hero">
    <div class="reviewer-hero-row">
        <div>
            <div class="reviewer-eyebrow">External Review Access</div>

            <h1 class="reviewer-title">Reviewer Dashboard</h1>

            <p class="reviewer-subtitle">
                View authorized case summaries, check document completion status, and submit external review notes
                for cases assigned to you.
            </p>
        </div>

        <div class="reviewer-actions">
            <a href="{{ route('reviewer.cases.index') }}" class="reviewer-btn reviewer-btn-primary">
                View Authorized Cases
            </a>
        </div>
    </div>
</div>

<div class="reviewer-notice">
    External reviewers only have access to selected authorized case summaries.
    Full child records, donor records, administrative controls, confidential notes, and final placement decisions are restricted.
</div>

<div class="reviewer-cards">
    <div class="reviewer-card">
        <div class="reviewer-card-title">Authorized Cases</div>
        <div class="reviewer-card-value">{{ $authorizedCases }}</div>
    </div>

    <div class="reviewer-card">
        <div class="reviewer-card-title">Pending Reviews</div>
        <div class="reviewer-card-value">{{ $pendingReviews }}</div>
    </div>

    <div class="reviewer-card">
        <div class="reviewer-card-title">Submitted Notes</div>
        <div class="reviewer-card-value">{{ $submittedNotes }}</div>
    </div>

    <div class="reviewer-card">
        <div class="reviewer-card-title">Document Checks</div>
        <div class="reviewer-card-value">{{ $documentChecks }}</div>
    </div>
</div>

<div class="reviewer-panel">
    <h2>Reviewer Access</h2>
    <p>
        This dashboard is intended for DSWD/RACCO external reviewers with limited access.
        Reviewers may view selected case summaries and provide feedback only when authorized.
    </p>
</div>
@endsection