{{--resources/views/reviewer/cases/--}}
@extends('layouts.dashboard', ['title' => 'Authorized Cases'])

@section('content')
<style>
    .reviewer-page-header {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .reviewer-page-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .reviewer-page-header h1 {
        margin: 0;
        color: #0f172a;
        font-size: 26px;
        font-weight: 900;
    }

    .reviewer-page-header p {
        margin: 8px 0 0;
        color: #64748b;
        line-height: 1.6;
        max-width: 760px;
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
        border: 1px solid #dbe3ef;
        background: #ffffff;
        color: #334155;
        white-space: nowrap;
    }

    .reviewer-case-panel {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .reviewer-table-wrap {
        overflow-x: auto;
    }

    .reviewer-table {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
    }

    .reviewer-table th {
        background: #f8fafc;
        color: #475569;
        text-align: left;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .reviewer-table td {
        padding: 16px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: top;
        color: #334155;
    }

    .reviewer-table tbody tr:hover {
        background: #f8fafc;
    }

    .case-code {
        color: #0f172a;
        font-weight: 900;
    }

    .muted-small {
        color: #64748b;
        font-size: 13px;
        margin-top: 4px;
    }

    .status-badge {
        display: inline-flex;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 900;
        background: #dbeafe;
        color: #1d4ed8;
        white-space: nowrap;
    }

    .permission-list {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .permission-badge {
        display: inline-flex;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        background: #f1f5f9;
        color: #475569;
    }

    .view-btn {
        display: inline-flex;
        border-radius: 12px;
        padding: 9px 13px;
        background: #2563eb;
        color: #ffffff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #64748b;
    }
</style>

<div class="reviewer-page-header">
    <div class="reviewer-page-row">
        <div>
            <h1>Authorized Cases</h1>
            <p>
                These are the adoption case summaries currently authorized for your review.
                Access is limited based on the permissions assigned by the administrator.
            </p>
        </div>

        <a href="{{ route('reviewer.dashboard') }}" class="reviewer-btn">
            Back to Dashboard
        </a>
    </div>
</div>

<div class="reviewer-case-panel">
    <div class="reviewer-table-wrap">
        <table class="reviewer-table">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Child Reference</th>
                    <th>Prospective Parent</th>
                    <th>Status</th>
                    <th>Document Progress</th>
                    <th>Permissions</th>
                    <th>Expires</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($caseAccesses as $access)
                    @php
                        $case = $access->adoptionCase;
                    @endphp

                    <tr>
                        <td>
                            <div class="case-code">{{ $case?->case_code ?? 'N/A' }}</div>
                            <div class="muted-small">{{ $case?->case_type_label ?? 'N/A' }}</div>
                        </td>

                        <td>
                            <strong>{{ $case?->child?->child_code ?? 'Restricted' }}</strong>
                            <div class="muted-small">
                                Full child profile is restricted.
                            </div>
                        </td>

                        <td>
                            {{ $case?->prospectiveParent?->name ?? 'Not assigned' }}
                        </td>

                        <td>
                            <span class="status-badge">
                                {{ $case?->status_label ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            {{ $case?->document_progress ?? '0/0' }}
                        </td>

                        <td>
                            <div class="permission-list">
                                @if($access->can_view_summary)
                                    <span class="permission-badge">Summary</span>
                                @endif

                                @if($access->can_view_document_status)
                                    <span class="permission-badge">Documents</span>
                                @endif

                                @if($access->can_submit_notes)
                                    <span class="permission-badge">Notes</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            {{ $access->expires_at?->format('M d, Y') ?? 'No expiry' }}
                        </td>

                        <td style="text-align: right;">
                            <a href="{{ route('reviewer.cases.show', $access) }}" class="view-btn">
                                View Summary
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                No authorized cases are currently assigned to you.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="padding: 16px;">
        {{ $caseAccesses->links() }}
    </div>
</div>
@endsection