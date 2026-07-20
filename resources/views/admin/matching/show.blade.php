@extends('layouts.dashboard', ['title' => 'Matching Results'])

@section('content')
    @php
        $results = $run->results ?? collect();

        $totalResults = $results->count();
        $recommendedCount = $results->where('status', 'recommended')->count();
        $convertedCount = $results->where('status', 'converted_to_case')->count();

        $averageChildScore = $totalResults > 0 ? round($results->avg('child_score'), 2) : 0;
        $averageParentScore = $totalResults > 0 ? round($results->avg('parent_score'), 2) : 0;

        $highestMatchScore = $totalResults > 0
            ? round($results->map(fn ($result) => (($result->child_score ?? 0) + ($result->parent_score ?? 0)) / 2)->max(), 2)
            : 0;

        $matchStrengthLabel = function ($score) {
            if ($score >= 85) {
                return [
                    'label' => 'Strong Match',
                    'level' => 'strong',
                    'color' => '#166534',
                    'background' => '#dcfce7',
                    'border' => '#86efac',
                    'icon' => 'bi-stars',
                ];
            }

            if ($score >= 70) {
                return [
                    'label' => 'Good Match',
                    'level' => 'good',
                    'color' => '#1d4ed8',
                    'background' => '#dbeafe',
                    'border' => '#93c5fd',
                    'icon' => 'bi-check-circle',
                ];
            }

            if ($score >= 50) {
                return [
                    'label' => 'Moderate Match',
                    'level' => 'moderate',
                    'color' => '#92400e',
                    'background' => '#fef3c7',
                    'border' => '#fcd34d',
                    'icon' => 'bi-exclamation-circle',
                ];
            }

            return [
                'label' => 'Needs Careful Review',
                'level' => 'careful',
                'color' => '#991b1b',
                'background' => '#fee2e2',
                'border' => '#fca5a5',
                'icon' => 'bi-shield-exclamation',
            ];
        };

        $statusBadgeStyle = function ($status) {
            return match ($status) {
                'recommended' => [
                    'label' => 'Recommended',
                    'color' => '#1d4ed8',
                    'background' => '#dbeafe',
                    'border' => '#93c5fd',
                    'icon' => 'bi-lightbulb',
                ],
                'converted_to_case' => [
                    'label' => 'Converted to Case',
                    'color' => '#166534',
                    'background' => '#dcfce7',
                    'border' => '#86efac',
                    'icon' => 'bi-folder-check',
                ],
                'rejected' => [
                    'label' => 'Rejected',
                    'color' => '#991b1b',
                    'background' => '#fee2e2',
                    'border' => '#fca5a5',
                    'icon' => 'bi-x-circle',
                ],
                default => [
                    'label' => ucwords(str_replace('_', ' ', $status)),
                    'color' => '#374151',
                    'background' => '#f3f4f6',
                    'border' => '#d1d5db',
                    'icon' => 'bi-info-circle',
                ],
            };
        };

        $formatValue = function ($value) {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if ($value === null || $value === '') {
                return 'N/A';
            }

            if (is_array($value)) {
                return json_encode($value, JSON_PRETTY_PRINT);
            }

            return $value;
        };

        $getInitials = function ($name) {
            if (!$name) {
                return 'NA';
            }

            $parts = collect(explode(' ', trim($name)))
                ->filter()
                ->values();

            if ($parts->count() === 1) {
                return strtoupper(substr($parts[0], 0, 2));
            }

            return strtoupper(substr($parts[0], 0, 1) . substr($parts[$parts->count() - 1], 0, 1));
        };
    @endphp

    <style>
        .matching-page {
            display: grid;
            gap: 18px;
        }

        .matching-hero {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #f8fafc 100%);
            border: 1px solid #dbeafe;
            border-radius: 22px;
            padding: 24px;
        }

        .matching-hero-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .matching-eyebrow {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .matching-hero h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
            color: #111827;
        }

        .matching-hero p {
            margin: 8px 0 0;
            color: #4b5563;
        }

        .matching-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .matching-notice {
            border-radius: 18px;
            padding: 18px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .matching-notice-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #ffedd5;
            color: #c2410c;
            font-size: 20px;
            flex: 0 0 auto;
        }

        .matching-notice strong {
            color: #9a3412;
        }

        .matching-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .matching-stat-card {
            border-radius: 18px;
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
        }

        .matching-stat-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .matching-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: #2563eb;
            font-size: 20px;
        }

        .matching-stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .matching-stat-value {
            font-size: 30px;
            font-weight: 900;
            margin-top: 10px;
            color: #111827;
        }

        .matching-stat-help {
            margin-top: 4px;
            color: #6b7280;
            font-size: 13px;
        }

        .matching-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .matching-filter-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .matching-filter-row input,
        .matching-filter-row select {
            min-height: 42px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            padding: 0 12px;
            background: #ffffff;
        }

        .matching-filter-row input {
            min-width: 260px;
        }

        .matching-small-button {
            min-height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            padding: 0 14px;
            cursor: pointer;
            font-weight: 700;
            color: #374151;
        }

        .matching-small-button:hover {
            background: #f9fafb;
        }

        .matching-results-count {
            color: #6b7280;
            font-size: 14px;
            margin-top: 6px;
        }

        .matching-grid-layout {
            display: grid;
            grid-template-columns: 380px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .match-box-list {
            display: grid;
            gap: 12px;
            max-height: 820px;
            overflow: auto;
            padding-right: 4px;
        }

        .match-box {
            width: 100%;
            text-align: left;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 14px;
            cursor: pointer;
            transition: 0.18s ease;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .match-box:hover {
            transform: translateY(-1px);
            border-color: #bfdbfe;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.10);
        }

        .match-box.active {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.14);
        }

        .match-box-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .match-box-score {
            font-size: 24px;
            font-weight: 950;
            color: #111827;
            line-height: 1;
            text-align: right;
        }

        .match-box-score small {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            margin-top: 4px;
        }

        .match-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .match-badge {
            display: inline-flex;
            gap: 5px;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid;
        }

        .match-box-pair {
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }

        .person-mini {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .person-avatar {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: #111827;
            font-weight: 900;
            border: 1px solid #e5e7eb;
            flex: 0 0 auto;
        }

        .person-mini strong {
            display: block;
            color: #111827;
            line-height: 1.2;
        }

        .person-mini small {
            color: #6b7280;
        }

        .match-box-footer {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            color: #6b7280;
            font-size: 13px;
        }

        .match-result-panel {
            display: none;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .match-result-panel.active {
            display: block;
        }

        .match-detail-header {
            padding: 22px;
            border-bottom: 1px solid #f3f4f6;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .match-detail-title h2 {
            margin: 8px 0 4px;
            color: #111827;
        }

        .match-detail-title p {
            margin: 0;
            color: #6b7280;
        }

        .overall-score-large {
            text-align: right;
        }

        .overall-score-large div {
            font-size: 44px;
            font-weight: 950;
            line-height: 1;
            color: #111827;
        }

        .overall-score-large small {
            color: #6b7280;
        }

        .match-detail-body {
            padding: 22px;
            display: grid;
            gap: 18px;
        }

        .detail-people-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .detail-person-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 16px;
            background: #ffffff;
        }

        .detail-person-card .person-avatar {
            width: 54px;
            height: 54px;
            border-radius: 18px;
        }

        .score-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .score-box {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 14px;
            background: #ffffff;
        }

        .score-box-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .score-box strong {
            color: #111827;
        }

        .score-box small {
            color: #6b7280;
        }

        .score-value {
            font-size: 26px;
            font-weight: 900;
            color: #111827;
        }

        .score-bar {
            height: 10px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
            margin: 10px 0;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        .ai-box {
            padding: 16px;
            border-radius: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .ai-avatar {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            background: #2563eb;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .ai-box p {
            margin: 6px 0 0;
            line-height: 1.65;
            color: #1f2937;
        }

        .reason-box {
            padding: 16px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }

        .reason-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .reason-chip {
            padding: 8px 10px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            font-size: 13px;
            color: #374151;
        }

        .review-reminder {
            padding: 14px 16px;
            border-radius: 18px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }

        .match-details {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .match-details summary {
            cursor: pointer;
            padding: 14px 16px;
            font-weight: 900;
            background: #f9fafb;
            color: #111827;
        }

        .structured-grid {
            padding: 16px;
            display: grid;
            gap: 10px;
            background: #ffffff;
        }

        .structured-row {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .structured-row strong {
            color: #374151;
        }

        .structured-row span {
            color: #4b5563;
            white-space: pre-wrap;
        }

        .match-card-footer {
            padding-top: 14px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .match-card-footer small {
            color: #6b7280;
        }

        .empty-matching-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-matching-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 22px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 28px;
        }

        .no-visible-results {
            display: none;
            text-align: center;
            padding: 34px 20px;
            border: 1px dashed #d1d5db;
            border-radius: 18px;
            background: #ffffff;
            color: #6b7280;
        }

        .no-visible-results.active {
            display: block;
        }

        @media (max-width: 1200px) {
            .matching-grid-layout {
                grid-template-columns: 1fr;
            }

            .match-box-list {
                max-height: none;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                overflow: visible;
                padding-right: 0;
            }
        }

        @media (max-width: 1100px) {
            .matching-stats-grid,
            .score-grid,
            .detail-people-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .matching-hero h1 {
                font-size: 24px;
            }

            .matching-stats-grid,
            .score-grid,
            .detail-people-grid,
            .match-box-list {
                grid-template-columns: 1fr;
            }

            .matching-filter-row,
            .matching-filter-row input,
            .matching-filter-row select,
            .matching-small-button {
                width: 100%;
            }

            .overall-score-large {
                text-align: left;
            }

            .structured-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="matching-page">
        <section class="matching-hero">
            <div class="matching-hero-header">
                <div>
                    <div class="matching-eyebrow">
                        <i class="bi bi-diagram-3"></i>
                        Stable Matching Batch
                    </div>

                    <h1>Matching Results: {{ $run->run_code }}</h1>

                    <p>
                        Generated by <strong>{{ $run->generator?->name ?? 'N/A' }}</strong>
                        on {{ $run->generated_at?->format('F d, Y h:i A') ?? 'N/A' }}.
                    </p>
                </div>

                <div class="matching-actions">
                    <a href="{{ route('admin.matching.index') }}" class="btn light">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </section>

        <section class="matching-notice">
            <div class="matching-notice-icon">
                <i class="bi bi-shield-check"></i>
            </div>

            <div>
                <strong>Human review is still required.</strong>
                <p style="margin: 6px 0 0;">
                    These are algorithmic recommendations only. Click a match box to view its full result.
                    AI may explain the recommendation, but it does not approve, finalize, or guarantee an adoption match.
                </p>
            </div>
        </section>

        <section class="matching-stats-grid">
            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Total Matches</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-collection"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $totalResults }}</div>
                <div class="matching-stat-help">All generated results in this batch</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Recommended</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $recommendedCount }}</div>
                <div class="matching-stat-help">Available for case creation</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Converted</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-folder-check"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $convertedCount }}</div>
                <div class="matching-stat-help">Already moved to adoption cases</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Highest Score</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $highestMatchScore }}%</div>
                <div class="matching-stat-help">Best overall compatibility score</div>
            </div>
        </section>

        <section class="matching-stats-grid">
            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Average Child Score</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-person-hearts"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $averageChildScore }}%</div>
                <div class="matching-stat-help">Parent suitability for child profile</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Average Parent Score</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="matching-stat-value">{{ $averageParentScore }}%</div>
                <div class="matching-stat-help">Child profile suitability for parent preference</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">Matching Method</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                </div>
                <div class="matching-stat-value" style="font-size: 24px;">Gale-Shapley</div>
                <div class="matching-stat-help">Stable matching recommendation process</div>
            </div>

            <div class="matching-stat-card">
                <div class="matching-stat-top">
                    <div class="matching-stat-label">AI Role</div>
                    <div class="matching-stat-icon">
                        <i class="bi bi-stars"></i>
                    </div>
                </div>
                <div class="matching-stat-value" style="font-size: 24px;">Explanation Only</div>
                <div class="matching-stat-help">AI explains, but does not decide</div>
            </div>
        </section>

        <section class="panel">
            <div class="matching-toolbar">
                <div>
                    <h2>Match Boxes</h2>
                    <p style="margin-bottom: 0;">
                        Click a box to view the full result on the right.
                    </p>

                    <div class="matching-results-count">
                        Showing <span id="visibleCount">{{ $totalResults }}</span> of {{ $totalResults }} recommendations
                    </div>
                </div>

                <div class="matching-filter-row">
                    <input
                        type="text"
                        id="matchSearch"
                        placeholder="Search child, code, parent, or email..."
                    >

                    <select id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="recommended">Recommended</option>
                        <option value="converted_to_case">Converted to Case</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <select id="strengthFilter">
                        <option value="">All Match Strengths</option>
                        <option value="strong">Strong Match</option>
                        <option value="good">Good Match</option>
                        <option value="moderate">Moderate Match</option>
                        <option value="careful">Needs Careful Review</option>
                    </select>

                    <select id="sortFilter">
                        <option value="score_desc">Highest Score First</option>
                        <option value="score_asc">Lowest Score First</option>
                        <option value="child_az">Child Name A-Z</option>
                        <option value="parent_az">Parent Name A-Z</option>
                    </select>

                    <button type="button" class="matching-small-button" id="clearFiltersBtn">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </button>
                </div>
            </div>
        </section>

        @if($results->isNotEmpty())
            <section class="matching-grid-layout">
                <div>
                    <div id="matchBoxList" class="match-box-list">
                        @foreach($results as $result)
                            @php
                                $combinedScore = round((($result->child_score ?? 0) + ($result->parent_score ?? 0)) / 2, 2);
                                $strength = $matchStrengthLabel($combinedScore);
                                $status = $statusBadgeStyle($result->status);

                                $childName = $result->child?->full_name ?? 'N/A';
                                $parentName = $result->prospectiveParent?->name ?? 'N/A';

                                $explanation = $result->explanation;

                                if (is_string($explanation)) {
                                    $decodedExplanation = json_decode($explanation, true);
                                    $explanation = is_array($decodedExplanation) ? $decodedExplanation : [];
                                }

                                if (!is_array($explanation)) {
                                    $explanation = [];
                                }

                                $aiSummary = $explanation['ai_summary'] ?? 'Click to view the full matching explanation.';
                            @endphp

                            <button
                                type="button"
                                class="match-box"
                                data-target="match-detail-{{ $result->id }}"
                                data-status="{{ $result->status }}"
                                data-strength="{{ $strength['level'] }}"
                                data-score="{{ $combinedScore }}"
                                data-child="{{ strtolower($childName) }}"
                                data-parent="{{ strtolower($parentName) }}"
                                data-search="{{ strtolower(($childName ?? '') . ' ' . ($result->child?->child_code ?? '') . ' ' . ($parentName ?? '') . ' ' . ($result->prospectiveParent?->email ?? '')) }}"
                            >
                                <div class="match-box-top">
                                    <div>
                                        <div class="match-badges">
                                            <span
                                                class="match-badge"
                                                style="color: {{ $strength['color'] }}; background: {{ $strength['background'] }}; border-color: {{ $strength['border'] }};"
                                            >
                                                <i class="bi {{ $strength['icon'] }}"></i>
                                                {{ $strength['label'] }}
                                            </span>

                                            <span
                                                class="match-badge"
                                                style="color: {{ $status['color'] }}; background: {{ $status['background'] }}; border-color: {{ $status['border'] }};"
                                            >
                                                <i class="bi {{ $status['icon'] }}"></i>
                                                {{ $status['label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="match-box-score">
                                        {{ $combinedScore }}%
                                        <small>Score</small>
                                    </div>
                                </div>

                                <div class="match-box-pair">
                                    <div class="person-mini">
                                        <div class="person-avatar">
                                            {{ $getInitials($childName) }}
                                        </div>

                                        <div>
                                            <strong>{{ $childName }}</strong>
                                            <small>{{ $result->child?->child_code ?? 'No child code' }}</small>
                                        </div>
                                    </div>

                                    <div class="person-mini">
                                        <div class="person-avatar">
                                            {{ $getInitials($parentName) }}
                                        </div>

                                        <div>
                                            <strong>{{ $parentName }}</strong>
                                            <small>{{ $result->prospectiveParent?->email ?? 'No email' }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="match-box-footer">
                                    <span>
                                        <i class="bi bi-cursor"></i>
                                        Click to view result
                                    </span>

                                    <span>
                                        Rank #{{ $result->rank_for_child ?? 'N/A' }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div id="noVisibleResults" class="no-visible-results">
                        <i class="bi bi-search" style="font-size: 28px;"></i>
                        <h3>No matching results found</h3>
                        <p>Try clearing your filters or searching another child or parent.</p>
                    </div>
                </div>

                <div id="matchDetailsContainer">
                    @foreach($results as $result)
                        @php
                            $combinedScore = round((($result->child_score ?? 0) + ($result->parent_score ?? 0)) / 2, 2);
                            $strength = $matchStrengthLabel($combinedScore);
                            $status = $statusBadgeStyle($result->status);

                            $childName = $result->child?->full_name ?? 'N/A';
                            $parentName = $result->prospectiveParent?->name ?? 'N/A';

                            $explanation = $result->explanation;

                            if (is_string($explanation)) {
                                $decodedExplanation = json_decode($explanation, true);
                                $explanation = is_array($decodedExplanation) ? $decodedExplanation : [];
                            }

                            if (!is_array($explanation)) {
                                $explanation = [];
                            }

                            $aiSummary = $explanation['ai_summary'] ?? null;
                            $ruleBasedReasons = $explanation['rule_based_reasons'] ?? [];
                            $reviewPoints = $explanation['review_points'] ?? [];
                            $reviewReminder = $explanation['review_reminder'] ?? 'This is only a system-generated recommendation. Final review and approval must still be done by authorized staff, social worker, and RACCO when applicable.';

                            $structuredFields = collect($explanation)
                                ->except([
                                    'ai_summary',
                                    'rule_based_reasons',
                                    'review_points',
                                    'review_reminder',
                                ])
                                ->all();

                            $childScoreWidth = min(100, max(0, $result->child_score ?? 0));
                            $parentScoreWidth = min(100, max(0, $result->parent_score ?? 0));
                        @endphp

                        <article
                            id="match-detail-{{ $result->id }}"
                            class="match-result-panel"
                        >
                            <div class="match-detail-header">
                                <div class="match-detail-title">
                                    <div class="match-badges">
                                        <span
                                            class="match-badge"
                                            style="color: {{ $strength['color'] }}; background: {{ $strength['background'] }}; border-color: {{ $strength['border'] }};"
                                        >
                                            <i class="bi {{ $strength['icon'] }}"></i>
                                            {{ $strength['label'] }}
                                        </span>

                                        <span
                                            class="match-badge"
                                            style="color: {{ $status['color'] }}; background: {{ $status['background'] }}; border-color: {{ $status['border'] }};"
                                        >
                                            <i class="bi {{ $status['icon'] }}"></i>
                                            {{ $status['label'] }}
                                        </span>
                                    </div>

                                    <h2>{{ $childName }} → {{ $parentName }}</h2>

                                    <p>
                                        This is the selected child-parent matching recommendation.
                                    </p>
                                </div>

                                <div class="overall-score-large">
                                    <div>{{ $combinedScore }}%</div>
                                    <small>Overall Match Score</small>
                                </div>
                            </div>

                            <div class="match-detail-body">
                                <div class="detail-people-grid">
                                    <div class="detail-person-card">
                                        <div class="person-mini">
                                            <div class="person-avatar">
                                                {{ $getInitials($childName) }}
                                            </div>

                                            <div>
                                                <strong>{{ $childName }}</strong>
                                                <small>Child Profile</small>
                                            </div>
                                        </div>

                                        <div style="margin-top: 14px; color: #4b5563;">
                                            <p><strong>Child Code:</strong> {{ $result->child?->child_code ?? 'N/A' }}</p>
                                            <p><strong>Sex:</strong> {{ ucfirst($result->child?->sex ?? 'N/A') }}</p>
                                            <p><strong>Eligibility:</strong> {{ ucwords(str_replace('_', ' ', $result->child?->adoption_eligibility_status ?? 'N/A')) }}</p>
                                            <p><strong>Case Status:</strong> {{ ucwords(str_replace('_', ' ', $result->child?->case_status ?? 'N/A')) }}</p>
                                        </div>
                                    </div>

                                    <div class="detail-person-card">
                                        <div class="person-mini">
                                            <div class="person-avatar">
                                                {{ $getInitials($parentName) }}
                                            </div>

                                            <div>
                                                <strong>{{ $parentName }}</strong>
                                                <small>Prospective Parent</small>
                                            </div>
                                        </div>

                                        <div style="margin-top: 14px; color: #4b5563;">
                                            <p><strong>Email:</strong> {{ $result->prospectiveParent?->email ?? 'N/A' }}</p>
                                            <p><strong>Preferred Sex:</strong> {{ ucfirst($result->prospectiveParent?->matchingProfile?->preferred_child_sex ?? 'N/A') }}</p>
                                            <p>
                                                <strong>Preferred Age:</strong>
                                                {{ $result->prospectiveParent?->matchingProfile?->min_child_age ?? 'N/A' }}
                                                -
                                                {{ $result->prospectiveParent?->matchingProfile?->max_child_age ?? 'N/A' }}
                                            </p>
                                            <p>
                                                <strong>Open to Special Needs:</strong>
                                                {{ $result->prospectiveParent?->matchingProfile?->open_to_special_needs ? 'Yes' : 'No' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="score-grid">
                                    <div class="score-box">
                                        <div class="score-box-head">
                                            <strong>Child Score</strong>
                                            <small>Rank #{{ $result->rank_for_child ?? 'N/A' }}</small>
                                        </div>

                                        <div class="score-value">{{ $result->child_score }}%</div>

                                        <div class="score-bar">
                                            <div class="score-bar-fill" style="width: {{ $childScoreWidth }}%; background: #2563eb;"></div>
                                        </div>

                                        <small>How well this parent fits the child’s needs.</small>
                                    </div>

                                    <div class="score-box">
                                        <div class="score-box-head">
                                            <strong>Parent Score</strong>
                                            <small>Rank #{{ $result->rank_for_parent ?? 'N/A' }}</small>
                                        </div>

                                        <div class="score-value">{{ $result->parent_score }}%</div>

                                        <div class="score-bar">
                                            <div class="score-bar-fill" style="width: {{ $parentScoreWidth }}%; background: #16a34a;"></div>
                                        </div>

                                        <small>How well the child profile fits the parent’s preferences.</small>
                                    </div>

                                    <div class="score-box">
                                        <div class="score-box-head">
                                            <strong>Review Priority</strong>
                                            <i class="bi bi-clipboard-check"></i>
                                        </div>

                                        <p style="margin: 8px 0; color: #4b5563;">
                                            @if($combinedScore >= 85)
                                                High compatibility. Review documents and notes before conversion.
                                            @elseif($combinedScore >= 70)
                                                Good compatibility. Staff validation is still required.
                                            @elseif($combinedScore >= 50)
                                                Possible compatibility. Review profile details closely.
                                            @else
                                                Low compatibility. Convert only if staff review finds strong supporting reasons.
                                            @endif
                                        </p>

                                        <small>This is decision support, not approval.</small>
                                    </div>
                                </div>

                                @if(!empty($aiSummary))
                                    <div class="ai-box">
                                        <div class="ai-avatar">
                                            AI
                                        </div>

                                        <div>
                                            <strong>AI Custom Explanation</strong>
                                            <p>{{ $aiSummary }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($ruleBasedReasons))
                                    <div class="reason-box">
                                        <strong>Why this match was recommended</strong>

                                        <div class="reason-list">
                                            @foreach($ruleBasedReasons as $reason)
                                                <span class="reason-chip">
                                                    <i class="bi bi-check2-circle"></i>
                                                    {{ $reason }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($reviewPoints))
                                    <div class="reason-box" style="background: #fff7ed; border-color: #fed7aa;">
                                        <strong style="color: #9a3412;">What staff should verify</strong>

                                        <div class="reason-list">
                                            @foreach($reviewPoints as $point)
                                                <span class="reason-chip">
                                                    <i class="bi bi-exclamation-circle"></i>
                                                    {{ $point }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="review-reminder">
                                    <strong>Review reminder:</strong>
                                    {{ $reviewReminder }}
                                </div>

                                @if(!empty($structuredFields))
                                    <details class="match-details">
                                        <summary>
                                            <i class="bi bi-list-check"></i>
                                            View structured matching data
                                        </summary>

                                        <div class="structured-grid">
                                            @foreach($structuredFields as $key => $value)
                                                <div class="structured-row">
                                                    <strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong>
                                                    <span>{{ $formatValue($value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif

                                <div class="match-card-footer">
                                    <small>
                                        Confirm eligibility, document completeness, and legal requirements before action.
                                    </small>

                                    @if($result->status === 'recommended')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.matching.create-case', $result) }}"
                                            onsubmit="return confirm('Convert this recommendation into an adoption case? Staff review is still required after case creation.');"
                                        >
                                            @csrf

                                            <button type="submit" class="btn secondary">
                                                <i class="bi bi-folder-plus"></i>
                                                Create Adoption Case
                                            </button>
                                        </form>
                                    @else
                                        <span class="btn light" style="cursor: default;">
                                            <i class="bi bi-check2-circle"></i>
                                            No Action Available
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @else
            <div class="panel empty-matching-state">
                <div class="empty-matching-icon">
                    <i class="bi bi-inboxes"></i>
                </div>

                <h2>No matches generated</h2>
                <p>
                    This matching run did not produce any recommendations.
                </p>

                <a href="{{ route('admin.matching.index') }}" class="btn secondary">
                    Back to Matching
                </a>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('matchSearch');
            const statusFilter = document.getElementById('statusFilter');
            const strengthFilter = document.getElementById('strengthFilter');
            const sortFilter = document.getElementById('sortFilter');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const visibleCount = document.getElementById('visibleCount');
            const matchBoxList = document.getElementById('matchBoxList');
            const noVisibleResults = document.getElementById('noVisibleResults');

            function getBoxes() {
                return Array.from(document.querySelectorAll('.match-box'));
            }

            function getPanels() {
                return Array.from(document.querySelectorAll('.match-result-panel'));
            }

            function showPanel(targetId) {
                getBoxes().forEach(box => {
                    box.classList.toggle('active', box.dataset.target === targetId);
                });

                getPanels().forEach(panel => {
                    panel.classList.toggle('active', panel.id === targetId);
                });
            }

            function firstVisibleBox() {
                return getBoxes().find(box => box.style.display !== 'none');
            }

            function ensureVisibleSelection() {
                const activeBox = document.querySelector('.match-box.active');

                if (activeBox && activeBox.style.display !== 'none') {
                    showPanel(activeBox.dataset.target);
                    return;
                }

                const firstBox = firstVisibleBox();

                if (firstBox) {
                    showPanel(firstBox.dataset.target);
                    return;
                }

                getPanels().forEach(panel => {
                    panel.classList.remove('active');
                });
            }

            function filterMatches() {
                const searchValue = (searchInput?.value || '').toLowerCase();
                const statusValue = statusFilter?.value || '';
                const strengthValue = strengthFilter?.value || '';

                let count = 0;

                getBoxes().forEach(box => {
                    const boxSearch = box.dataset.search || '';
                    const boxStatus = box.dataset.status || '';
                    const boxStrength = box.dataset.strength || '';

                    const matchesSearch = boxSearch.includes(searchValue);
                    const matchesStatus = !statusValue || boxStatus === statusValue;
                    const matchesStrength = !strengthValue || boxStrength === strengthValue;

                    const shouldShow = matchesSearch && matchesStatus && matchesStrength;

                    box.style.display = shouldShow ? 'block' : 'none';

                    if (shouldShow) {
                        count++;
                    }
                });

                if (visibleCount) {
                    visibleCount.textContent = count;
                }

                if (noVisibleResults) {
                    noVisibleResults.classList.toggle('active', count === 0);
                }

                ensureVisibleSelection();
            }

            function sortMatches() {
                if (!matchBoxList || !sortFilter) {
                    return;
                }

                const boxes = getBoxes();
                const sortValue = sortFilter.value;

                boxes.sort((a, b) => {
                    if (sortValue === 'score_asc') {
                        return Number(a.dataset.score || 0) - Number(b.dataset.score || 0);
                    }

                    if (sortValue === 'child_az') {
                        return (a.dataset.child || '').localeCompare(b.dataset.child || '');
                    }

                    if (sortValue === 'parent_az') {
                        return (a.dataset.parent || '').localeCompare(b.dataset.parent || '');
                    }

                    return Number(b.dataset.score || 0) - Number(a.dataset.score || 0);
                });

                boxes.forEach(box => matchBoxList.appendChild(box));
                filterMatches();
            }

            getBoxes().forEach(box => {
                box.addEventListener('click', function () {
                    showPanel(box.dataset.target);
                });
            });

            searchInput?.addEventListener('input', filterMatches);
            statusFilter?.addEventListener('change', filterMatches);
            strengthFilter?.addEventListener('change', filterMatches);
            sortFilter?.addEventListener('change', sortMatches);

            clearFiltersBtn?.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }

                if (statusFilter) {
                    statusFilter.value = '';
                }

                if (strengthFilter) {
                    strengthFilter.value = '';
                }

                if (sortFilter) {
                    sortFilter.value = 'score_desc';
                }

                sortMatches();
                filterMatches();
            });

            sortMatches();

            const firstBox = firstVisibleBox();

            if (firstBox) {
                showPanel(firstBox.dataset.target);
            }

            filterMatches();
        });
    </script>
@endsection