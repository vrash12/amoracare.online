@extends('layouts.dashboard', ['title' => 'Reports and Analytics'])

@section('content')
    @php
        $totalChildren = (int) ($summary['total_children'] ?? 0);
        $eligibleChildren = (int) ($summary['eligible_children'] ?? 0);
        $availableChildren = (int) ($summary['available_children'] ?? 0);
        $specialNeedsChildren = (int) ($summary['special_needs_children'] ?? 0);

        $totalCases = (int) ($summary['total_adoption_cases'] ?? 0);
        $activeCases = (int) ($summary['active_adoption_cases'] ?? 0);
        $finalizedCases = (int) ($summary['finalized_adoption_cases'] ?? 0);
        $cancelledCases = (int) ($summary['cancelled_adoption_cases'] ?? 0);

        $totalDocuments = (int) ($summary['total_documents'] ?? 0);
        $verifiedDocuments = (int) ($summary['verified_documents'] ?? 0);
        $pendingDocuments = (int) ($summary['pending_documents'] ?? 0);
        $rejectedDocuments = (int) ($summary['rejected_documents'] ?? 0);
        $expiredDocuments = (int) ($summary['expired_documents'] ?? 0);

        $totalDonations = (int) ($summary['total_donations'] ?? 0);
        $verifiedDonations = (int) ($summary['verified_donations'] ?? 0);
        $cashDonationTotal = (float) ($summary['cash_donation_total'] ?? 0);
        $totalDonors = (int) ($summary['total_donors'] ?? 0);

        $eligibleRate = $totalChildren > 0
            ? round(($eligibleChildren / $totalChildren) * 100)
            : 0;

        $finalizedRate = $totalCases > 0
            ? round(($finalizedCases / $totalCases) * 100)
            : 0;

        $verifiedDonationRate = $totalDonations > 0
            ? round(($verifiedDonations / $totalDonations) * 100)
            : 0;

        $documentProgress = (int) ($summary['document_progress_percent'] ?? 0);

        $dateRangeLabel = $from->format('M d, Y') . ' – ' . $to->format('M d, Y');
    @endphp

    <style>
        .reports-page {
            --reports-primary: #8d3d27;
            --reports-primary-dark: #6f2f1d;
            --reports-primary-soft: #fff2ec;
            --reports-blue: #2563eb;
            --reports-blue-soft: #eff6ff;
            --reports-green: #15803d;
            --reports-green-soft: #ecfdf3;
            --reports-amber: #b45309;
            --reports-amber-soft: #fff7ed;
            --reports-red: #b91c1c;
            --reports-red-soft: #fff1f2;
            --reports-purple: #7c3aed;
            --reports-purple-soft: #f5f3ff;
            --reports-text: #172033;
            --reports-muted: #667085;
            --reports-line: #e5e9f0;
            --reports-surface: #ffffff;
            --reports-soft: #f7f9fc;
            display: grid;
            gap: 18px;
            color: var(--reports-text);
        }

        .reports-page *,
        .reports-page *::before,
        .reports-page *::after {
            box-sizing: border-box;
        }

        .reports-hero {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            padding: 25px;
            border: 1px solid #ead8d0;
            border-radius: 23px;
            background:
                radial-gradient(circle at 92% 12%, rgba(141, 61, 39, 0.14), transparent 30%),
                linear-gradient(135deg, #fff9f6 0%, #ffffff 62%, #f8fafc 100%);
            box-shadow: 0 16px 38px rgba(20, 31, 51, 0.06);
        }

        .reports-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 9px;
            padding: 7px 11px;
            border: 1px solid #edc7b8;
            border-radius: 999px;
            background: var(--reports-primary-soft);
            color: var(--reports-primary-dark);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .reports-hero h1 {
            margin: 0;
            color: #101828;
            font-size: clamp(28px, 3.4vw, 39px);
            line-height: 1.15;
        }

        .reports-hero p {
            max-width: 760px;
            margin: 9px 0 0;
            color: var(--reports-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .reports-hero-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .reports-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 1px solid var(--reports-line);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #475467;
            font-size: 11px;
            font-weight: 850;
        }

        .reports-links {
            display: grid;
            gap: 8px;
            min-width: 215px;
        }

        .reports-btn {
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
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            transition: .16s ease;
        }

        .reports-btn:hover {
            border-color: #b8c0cc;
            background: #f9fafb;
            color: #101828;
        }

        .reports-btn.is-primary {
            border-color: var(--reports-primary);
            background: var(--reports-primary);
            color: #ffffff;
        }

        .reports-btn.is-primary:hover {
            border-color: var(--reports-primary-dark);
            background: var(--reports-primary-dark);
            color: #ffffff;
        }

        .reports-filter-card,
        .reports-panel {
            border: 1px solid var(--reports-line);
            border-radius: 19px;
            background: var(--reports-surface);
            box-shadow: 0 11px 27px rgba(20, 31, 51, 0.045);
        }

        .reports-filter-card {
            padding: 17px;
        }

        .reports-filter-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr)) auto auto;
            gap: 12px;
            align-items: end;
        }

        .reports-field label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 12px;
            font-weight: 900;
        }

        .reports-field input {
            width: 100%;
            min-height: 42px;
            padding: 0 12px;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: #ffffff;
            color: #101828;
            font: inherit;
            outline: none;
        }

        .reports-field input:focus {
            border-color: var(--reports-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .1);
        }

        .reports-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 13px;
        }

        .reports-stat {
            min-width: 0;
            padding: 17px;
            border: 1px solid var(--reports-line);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(20, 31, 51, .04);
        }

        .reports-stat-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
        }

        .reports-stat-label {
            color: var(--reports-muted);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .reports-stat-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 12px;
            background: var(--reports-blue-soft);
            color: var(--reports-blue);
            font-size: 18px;
        }

        .reports-stat.is-green .reports-stat-icon {
            background: var(--reports-green-soft);
            color: var(--reports-green);
        }

        .reports-stat.is-purple .reports-stat-icon {
            background: var(--reports-purple-soft);
            color: var(--reports-purple);
        }

        .reports-stat.is-amber .reports-stat-icon {
            background: var(--reports-amber-soft);
            color: var(--reports-amber);
        }

        .reports-stat-value {
            margin-top: 11px;
            color: #101828;
            font-size: 28px;
            font-weight: 950;
            line-height: 1;
            overflow-wrap: anywhere;
        }

        .reports-stat-help {
            margin-top: 6px;
            color: var(--reports-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .reports-grid-two {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .reports-panel {
            min-width: 0;
            overflow: hidden;
        }

        .reports-panel-header {
            display: flex;
            justify-content: space-between;
            gap: 13px;
            align-items: flex-start;
            padding: 17px 18px 13px;
            border-bottom: 1px solid var(--reports-line);
        }

        .reports-panel-header h2 {
            margin: 0;
            color: #101828;
            font-size: 17px;
        }

        .reports-panel-header p {
            margin: 5px 0 0;
            color: var(--reports-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .reports-panel-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 8px;
            border-radius: 999px;
            background: var(--reports-soft);
            color: #475467;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .reports-chart-wrap {
            height: 315px;
            padding: 16px;
        }

        .reports-breakdown {
            display: grid;
            gap: 12px;
            padding: 16px 18px 18px;
        }

        .reports-breakdown-row {
            display: grid;
            gap: 6px;
        }

        .reports-breakdown-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #344054;
            font-size: 12px;
        }

        .reports-breakdown-top strong {
            color: #101828;
        }

        .reports-progress {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #eef1f5;
        }

        .reports-progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--reports-blue);
        }

        .reports-progress.is-green > span {
            background: var(--reports-green);
        }

        .reports-progress.is-amber > span {
            background: var(--reports-amber);
        }

        .reports-table-wrap {
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }

        .reports-table th,
        .reports-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            vertical-align: middle;
        }

        .reports-table th {
            background: #fbfcfe;
            color: #667085;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .reports-table td {
            color: #344054;
            font-size: 11px;
        }

        .reports-table tr:last-child td {
            border-bottom: none;
        }

        .reports-code {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .reports-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 8px;
            border-radius: 999px;
            background: #f2f4f7;
            color: #475467;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .reports-empty {
            padding: 30px 18px;
            color: var(--reports-muted);
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .reports-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 850px) {
            .reports-hero {
                grid-template-columns: 1fr;
            }

            .reports-links {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                min-width: 0;
            }

            .reports-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .reports-grid-two {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .reports-hero {
                padding: 20px;
            }

            .reports-links,
            .reports-filter-form,
            .reports-stats {
                grid-template-columns: 1fr;
            }

            .reports-btn {
                width: 100%;
            }

            .reports-chart-wrap {
                height: 280px;
            }
        }
    </style>

    <div class="reports-page">
        <section class="reports-hero">
            <div>
                <div class="reports-eyebrow">
                    <i class="bi bi-bar-chart-line"></i>
                    Administrative analytics
                </div>

                <h1>Reports and Analytics</h1>

                <p>
                    Monitor child records, adoption cases, document reviews, and donations
                    for the selected reporting period.
                </p>

                <div class="reports-hero-meta">
                    <span class="reports-meta-pill">
                        <i class="bi bi-calendar3"></i>
                        {{ $dateRangeLabel }}
                    </span>

                    <span class="reports-meta-pill">
                        <i class="bi bi-shield-check"></i>
                        Authorized admin view
                    </span>
                </div>
            </div>

            <div class="reports-links">
                <a href="{{ route('admin.reports.children', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="reports-btn">
                    <i class="bi bi-people"></i>
                    Child report
                </a>

                <a href="{{ route('admin.reports.adoption-cases', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="reports-btn">
                    <i class="bi bi-folder2-open"></i>
                    Case report
                </a>

                <a href="{{ route('admin.reports.donations', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="reports-btn">
                    <i class="bi bi-heart"></i>
                    Donation report
                </a>
            </div>
        </section>

        <section class="reports-filter-card">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="reports-filter-form">
                <div class="reports-field">
                    <label for="from">Date from</label>
                    <input
                        type="date"
                        id="from"
                        name="from"
                        value="{{ $from->format('Y-m-d') }}"
                        required
                    >
                </div>

                <div class="reports-field">
                    <label for="to">Date to</label>
                    <input
                        type="date"
                        id="to"
                        name="to"
                        value="{{ $to->format('Y-m-d') }}"
                        required
                    >
                </div>

                <button type="submit" class="reports-btn is-primary">
                    <i class="bi bi-funnel"></i>
                    Apply filter
                </button>

                <a href="{{ route('admin.reports.index') }}" class="reports-btn">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset
                </a>
            </form>
        </section>

        <section class="reports-stats">
            <article class="reports-stat">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Total children</span>
                    <span class="reports-stat-icon"><i class="bi bi-people"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($totalChildren) }}</div>
                <div class="reports-stat-help">
                    {{ $eligibleRate }}% eligible · {{ number_format($availableChildren) }} available
                </div>
            </article>

            <article class="reports-stat is-purple">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Adoption cases</span>
                    <span class="reports-stat-icon"><i class="bi bi-folder2-open"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($totalCases) }}</div>
                <div class="reports-stat-help">
                    {{ number_format($activeCases) }} active · {{ $finalizedRate }}% finalized
                </div>
            </article>

            <article class="reports-stat is-green">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Verified documents</span>
                    <span class="reports-stat-icon"><i class="bi bi-file-earmark-check"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($verifiedDocuments) }}</div>
                <div class="reports-stat-help">
                    {{ $documentProgress }}% of {{ number_format($totalDocuments) }} documents verified
                </div>
            </article>

            <article class="reports-stat is-amber">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Cash donations</span>
                    <span class="reports-stat-icon"><i class="bi bi-cash-stack"></i></span>
                </div>
                <div class="reports-stat-value">₱{{ number_format($cashDonationTotal, 2) }}</div>
                <div class="reports-stat-help">
                    {{ number_format($totalDonations) }} donations · {{ $verifiedDonationRate }}% verified
                </div>
            </article>
        </section>

        <section class="reports-stats">
            <article class="reports-stat is-green">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Eligible children</span>
                    <span class="reports-stat-icon"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($eligibleChildren) }}</div>
                <div class="reports-stat-help">
                    {{ number_format($specialNeedsChildren) }} children marked with special needs
                </div>
            </article>

            <article class="reports-stat">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Case outcomes</span>
                    <span class="reports-stat-icon"><i class="bi bi-check2-circle"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($finalizedCases) }}</div>
                <div class="reports-stat-help">
                    {{ number_format($cancelledCases) }} cancelled in the selected period
                </div>
            </article>

            <article class="reports-stat is-amber">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Documents pending</span>
                    <span class="reports-stat-icon"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($pendingDocuments) }}</div>
                <div class="reports-stat-help">
                    {{ number_format($rejectedDocuments) }} rejected · {{ number_format($expiredDocuments) }} expired
                </div>
            </article>

            <article class="reports-stat is-purple">
                <div class="reports-stat-top">
                    <span class="reports-stat-label">Registered donors</span>
                    <span class="reports-stat-icon"><i class="bi bi-heart"></i></span>
                </div>
                <div class="reports-stat-value">{{ number_format($totalDonors) }}</div>
                <div class="reports-stat-help">
                    {{ number_format((int) ($summary['prospective_parents'] ?? 0)) }} prospective parents ·
                    {{ number_format((int) ($summary['external_reviewers'] ?? 0)) }} reviewers
                </div>
            </article>
        </section>

        <section class="reports-grid-two">
            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Daily adoption cases</h2>
                        <p>Cases created during the selected reporting period.</p>
                    </div>
                    <span class="reports-panel-badge"><i class="bi bi-graph-up"></i> Trend</span>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="dailyCasesChart"></canvas>
                </div>
            </article>

            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Daily donations</h2>
                        <p>Donation count and recorded cash value by day.</p>
                    </div>
                    <span class="reports-panel-badge"><i class="bi bi-cash-coin"></i> Activity</span>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="dailyDonationsChart"></canvas>
                </div>
            </article>
        </section>

        <section class="reports-grid-two">
            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Adoption case status</h2>
                        <p>Distribution of cases by current status.</p>
                    </div>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="caseStatusChart"></canvas>
                </div>
            </article>

            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Child eligibility</h2>
                        <p>Eligibility distribution for child records created in range.</p>
                    </div>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="childEligibilityChart"></canvas>
                </div>
            </article>
        </section>

        <section class="reports-grid-two">
            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Document review status</h2>
                        <p>Current review state of adoption case documents.</p>
                    </div>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="documentStatusChart"></canvas>
                </div>
            </article>

            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Adoption case types</h2>
                        <p>Cases grouped according to their adoption category.</p>
                    </div>
                </header>
                <div class="reports-chart-wrap">
                    <canvas id="caseTypeChart"></canvas>
                </div>
            </article>
        </section>

        <section class="reports-grid-two">
            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Child case status summary</h2>
                        <p>Record count and proportion per child case status.</p>
                    </div>
                </header>

                <div class="reports-breakdown">
                    @forelse($childrenByCaseStatus as $row)
                        @php
                            $count = (int) $row->total;
                            $percent = $totalChildren > 0
                                ? round(($count / $totalChildren) * 100)
                                : 0;
                            $label = \App\Models\Child::CASE_STATUSES[$row->case_status]
                                ?? ucwords(str_replace('_', ' ', (string) $row->case_status));
                        @endphp

                        <div class="reports-breakdown-row">
                            <div class="reports-breakdown-top">
                                <span>{{ $label }}</span>
                                <strong>{{ number_format($count) }} ({{ $percent }}%)</strong>
                            </div>
                            <div class="reports-progress"><span style="width: {{ $percent }}%"></span></div>
                        </div>
                    @empty
                        <div class="reports-empty">No child status records for this period.</div>
                    @endforelse
                </div>
            </article>

            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Donation purpose summary</h2>
                        <p>Donation records and cash totals grouped by purpose.</p>
                    </div>
                </header>

                <div class="reports-breakdown">
                    @forelse($donationsByPurpose as $row)
                        @php
                            $count = (int) $row->total;
                            $percent = $totalDonations > 0
                                ? round(($count / $totalDonations) * 100)
                                : 0;
                            $label = \App\Models\Donation::PURPOSES[$row->purpose]
                                ?? ucwords(str_replace('_', ' ', (string) $row->purpose));
                        @endphp

                        <div class="reports-breakdown-row">
                            <div class="reports-breakdown-top">
                                <span>{{ $label }}</span>
                                <strong>{{ number_format($count) }} · ₱{{ number_format((float) $row->cash_total, 2) }}</strong>
                            </div>
                            <div class="reports-progress is-amber"><span style="width: {{ $percent }}%"></span></div>
                        </div>
                    @empty
                        <div class="reports-empty">No donation records for this period.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="reports-grid-two">
            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Recent child profiles</h2>
                        <p>Latest child records created within the selected period.</p>
                    </div>
                </header>

                <div class="reports-table-wrap">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Child</th>
                                <th>Status</th>
                                <th>Eligibility</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentChildren as $child)
                                <tr>
                                    <td><span class="reports-code">{{ $child->child_code }}</span></td>
                                    <td>
                                        <strong>{{ $child->full_name }}</strong>
                                        @if($child->nickname)
                                            <div style="color:#667085; margin-top:2px;">{{ $child->nickname }}</div>
                                        @endif
                                    </td>
                                    <td><span class="reports-status">{{ $child->case_status_label }}</span></td>
                                    <td><span class="reports-status">{{ $child->eligibility_status_label }}</span></td>
                                    <td>{{ optional($child->created_at)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="reports-empty">No recent child profiles.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="reports-panel">
                <header class="reports-panel-header">
                    <div>
                        <h2>Recent adoption cases</h2>
                        <p>Latest adoption cases opened within the selected period.</p>
                    </div>
                </header>

                <div class="reports-table-wrap">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Child</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdoptionCases as $case)
                                <tr>
                                    <td><span class="reports-code">{{ $case->case_code }}</span></td>
                                    <td>{{ $case->child?->full_name ?? 'N/A' }}</td>
                                    <td>{{ $case->prospectiveParent?->name ?? 'N/A' }}</td>
                                    <td><span class="reports-status">{{ $case->status_label }}</span></td>
                                    <td>{{ optional($case->created_at)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="reports-empty">No recent adoption cases.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="reports-panel">
            <header class="reports-panel-header">
                <div>
                    <h2>Recent donations</h2>
                    <p>Latest donations recorded within the selected period.</p>
                </div>
            </header>

            <div class="reports-table-wrap">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Donation</th>
                            <th>Donor</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Cash amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDonations as $donation)
                            <tr>
                                <td><span class="reports-code">{{ $donation->donation_code }}</span></td>
                                <td>{{ $donation->donor?->name ?? 'Anonymous / N/A' }}</td>
                                <td><span class="reports-status">{{ $donation->donation_type_label }}</span></td>
                                <td>{{ $donation->purpose_label }}</td>
                                <td>₱{{ number_format((float) $donation->cash_amount, 2) }}</td>
                                <td><span class="reports-status">{{ $donation->status_label }}</span></td>
                                <td>{{ optional($donation->donation_date)->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="reports-empty">No recent donations.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            const colors = [
                '#2563eb',
                '#15803d',
                '#b45309',
                '#b91c1c',
                '#7c3aed',
                '#0891b2',
                '#db2777',
                '#475569'
            ];

            const chartData = @json($chartData);

            const defaultPlugins = {
                legend: {
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        font: { size: 11 }
                    }
                }
            };

            function createChart(id, config) {
                const canvas = document.getElementById(id);

                if (!canvas) {
                    return;
                }

                new Chart(canvas, config);
            }

            createChart('dailyCasesChart', {
                type: 'line',
                data: {
                    labels: chartData.dailyCases?.labels ?? [],
                    datasets: [{
                        label: 'Adoption cases',
                        data: chartData.dailyCases?.values ?? [],
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.12)',
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: defaultPlugins,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });

            createChart('dailyDonationsChart', {
                type: 'bar',
                data: {
                    labels: chartData.dailyDonations?.labels ?? [],
                    datasets: [
                        {
                            label: 'Donation count',
                            data: chartData.dailyDonations?.values ?? [],
                            backgroundColor: 'rgba(21, 128, 61, 0.72)',
                            borderColor: '#15803d',
                            borderWidth: 1,
                            yAxisID: 'countAxis'
                        },
                        {
                            label: 'Cash total',
                            data: chartData.dailyCashDonations?.values ?? [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            type: 'line',
                            yAxisID: 'cashAxis'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: defaultPlugins,
                    scales: {
                        countAxis: {
                            beginAtZero: true,
                            position: 'left',
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Donation count' }
                        },
                        cashAxis: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Cash amount' }
                        }
                    }
                }
            });

            createChart('caseStatusChart', {
                type: 'bar',
                data: {
                    labels: chartData.casesByStatus?.labels ?? [],
                    datasets: [{
                        label: 'Cases',
                        data: chartData.casesByStatus?.values ?? [],
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });

            createChart('childEligibilityChart', {
                type: 'doughnut',
                data: {
                    labels: chartData.childrenByEligibility?.labels ?? [],
                    datasets: [{
                        data: chartData.childrenByEligibility?.values ?? [],
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: defaultPlugins
                }
            });

            createChart('documentStatusChart', {
                type: 'doughnut',
                data: {
                    labels: chartData.documentsByStatus?.labels ?? [],
                    datasets: [{
                        data: chartData.documentsByStatus?.values ?? [],
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: defaultPlugins
                }
            });

            createChart('caseTypeChart', {
                type: 'pie',
                data: {
                    labels: chartData.casesByType?.labels ?? [],
                    datasets: [{
                        data: chartData.casesByType?.values ?? [],
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: defaultPlugins
                }
            });
        });
    </script>
@endsection
