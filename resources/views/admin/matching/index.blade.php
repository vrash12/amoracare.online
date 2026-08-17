@extends('layouts.dashboard', ['title' => 'Adoption Matching'])

@section('content')
    @php
        $canRunMatching = $eligibleChildrenCount > 0 && $activeParentsWithProfilesCount > 0;
    @endphp

    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Adoption Matching</h2>
                <p>
                    Generate child-centered stable matching recommendations for authorized staff review.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.matching.run') }}">
                @csrf

                <button
                    type="submit"
                    class="btn"
                    @if(!$canRunMatching) disabled style="opacity: 0.6; cursor: not-allowed;" @endif
                    onclick="return confirm('Run stable matching now? This will create a new recommendation batch for staff review.');"
                >
                    Run Matching
                </button>
            </form>
        </div>
    </div>

    <div class="panel" style="border-left: 5px solid #2563eb;">
        <h2 style="margin-top: 0;">What This Feature Does</h2>
        <p>
            This module uses the Gale-Shapley stable matching method to create recommended pairings between
            eligible children and active prospective parents. The rankings are generated from system data and
            matching profiles. They are not direct choices made by the child or parent.
        </p>
        <p style="margin-bottom: 0;">
            The result is a <strong>recommendation only</strong>. Authorized staff, reviewers, and RACCO/NACC-related
            processes must still review the recommendation before any adoption case action is taken.
        </p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Eligible Children</div>
            <div class="card-value">{{ $eligibleChildrenCount }}</div>
            <small>Eligible, available, and without active adoption case</small>
        </div>

        <div class="card">
            <div class="card-title">Ready Parent Profiles</div>
            <div class="card-value">{{ $activeParentsWithProfilesCount }}</div>
            <small>Active parents with matching profiles and no active case</small>
        </div>

        <div class="card">
            <div class="card-title">Total Matching Runs</div>
            <div class="card-value">{{ $totalRunsCount }}</div>
            <small>{{ $completedRunsCount }} completed runs</small>
        </div>

        <div class="card">
            <div class="card-title">Pending Recommendations</div>
            <div class="card-value">{{ $pendingRecommendationsCount }}</div>
            <small>Recommendations not yet converted into cases</small>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Total Recommendations</div>
            <div class="card-value">{{ $totalRecommendationsCount }}</div>
            <small>All generated matching results</small>
        </div>

        <div class="card">
            <div class="card-title">Converted to Cases</div>
            <div class="card-value">{{ $convertedRecommendationsCount }}</div>
            <small>Recommendations moved to adoption case workflow</small>
        </div>

        <div class="card">
            <div class="card-title">Matching Method</div>
            <div class="card-value" style="font-size: 22px;">Gale-Shapley</div>
            <small>Stable matching recommendation algorithm</small>
        </div>

        <div class="card">
            <div class="card-title">Current Readiness</div>
            <div class="card-value" style="font-size: 22px;">
                {{ $canRunMatching ? 'Ready' : 'Not Ready' }}
            </div>
            <small>
                @if($canRunMatching)
                    Matching can be run now.
                @else
                    Requires at least one eligible child and one ready parent.
                @endif
            </small>
        </div>
    </div>

    @if(!$canRunMatching)
        <div class="notice">
            Matching cannot run yet. Make sure there is at least one child marked as
            <strong>Eligible</strong> and <strong>Available for Adoption</strong>, and at least one active prospective
            parent with a completed matching profile.
        </div>
    @endif

    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
            <div>
                <h2>How the Matching Process Works</h2>
                <p>
                    The system follows a controlled process before showing recommendations to staff.
                </p>
            </div>

            <span class="btn light" style="cursor: default;">
                Decision-Support Only
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 16px;">
            <div style="padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 800; color: #2563eb;">1</div>
                <strong>Filter Eligible Children</strong>
                <p style="margin-bottom: 0;">
                    The system includes only children marked eligible and available for adoption.
                </p>
            </div>

            <div style="padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 800; color: #2563eb;">2</div>
                <strong>Filter Ready Parents</strong>
                <p style="margin-bottom: 0;">
                    Only active prospective parents with matching profiles are included.
                </p>
            </div>

            <div style="padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 800; color: #2563eb;">3</div>
                <strong>Compute Suitability</strong>
                <p style="margin-bottom: 0;">
                    The matcher scores possible pairings using child-centered and parent-readiness criteria.
                </p>
            </div>

            <div style="padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 800; color: #2563eb;">4</div>
                <strong>Run Stable Matching</strong>
                <p style="margin-bottom: 0;">
                    Gale-Shapley creates stable recommendation pairs from the generated rankings.
                </p>
            </div>

            <div style="padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #e5e7eb;">
                <div style="font-size: 28px; font-weight: 800; color: #2563eb;">5</div>
                <strong>Staff Review</strong>
                <p style="margin-bottom: 0;">
                    Admin, reviewer, and authorized staff must review before case creation.
                </p>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Who Gets Included?</h2>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 14px;">
                <h3 style="margin-top: 0;">Child Requirements</h3>

                <div style="display: grid; gap: 10px;">
                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>Adoption eligibility status is <strong>Eligible</strong>.</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>Case status is <strong>Available for Adoption</strong>.</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>No active adoption case is already assigned.</span>
                    </div>
                </div>
            </div>

            <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 14px;">
                <h3 style="margin-top: 0;">Prospective Parent Requirements</h3>

                <div style="display: grid; gap: 10px;">
                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>User role is <strong>Prospective Parent</strong>.</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>Account status is <strong>Active</strong>.</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>A matching profile exists.</span>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <strong style="color: #16a34a;">✓</strong>
                        <span>No active adoption case is already assigned.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>After a Recommendation Is Generated</h2>

        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;">
            <div style="padding: 16px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 14px;">
                <strong>Recommended</strong>
                <p style="margin-bottom: 0;">
                    The algorithm suggests a possible pairing for review.
                </p>
            </div>

            <div style="padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px;">
                <strong>Admin Review</strong>
                <p style="margin-bottom: 0;">
                    Staff checks child record, parent profile, documents, and notes.
                </p>
            </div>

            <div style="padding: 16px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 14px;">
                <strong>Reviewer / RACCO Review</strong>
                <p style="margin-bottom: 0;">
                    Authorized review may validate or reject the recommendation.
                </p>
            </div>

            <div style="padding: 16px; background: #ecfdf5; border: 1px solid #bbf7d0; border-radius: 14px;">
                <strong>Case Creation</strong>
                <p style="margin-bottom: 0;">
                    If approved, staff may convert the recommendation into an adoption case.
                </p>
            </div>
        </div>
    </div>

    @if($latestRun)
        <div class="panel">
            <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
                <div>
                    <h2>Latest Matching Run</h2>
                    <p>
                        Last generated recommendation batch.
                    </p>
                </div>

                <a href="{{ route('admin.matching.show', $latestRun) }}" class="btn secondary">
                    View Latest Results
                </a>
            </div>

            <div class="cards">
                <div class="card">
                    <div class="card-title">Run Code</div>
                    <div class="card-value" style="font-size: 22px;">
                        {{ $latestRun->run_code }}
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Generated By</div>
                    <div class="card-value" style="font-size: 22px;">
                        {{ $latestRun->generator?->name ?? 'N/A' }}
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Generated At</div>
                    <div class="card-value" style="font-size: 18px;">
                        @if($latestRun->generated_at)
                            {{ $latestRun->generated_at->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }} PHT
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Results</div>
                    <div class="card-value">
                        {{ $latestRun->results_count }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Matching Run History</h2>
                <p>
                    Each run is saved as a separate recommendation batch for audit and review.
                </p>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px;">Run Code</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">Generated By</th>
                        <th style="text-align: left; padding: 12px;">Generated At</th>
                        <th style="text-align: left; padding: 12px;">Matches</th>
                        <th style="text-align: right; padding: 12px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td style="padding: 12px;">
                                <strong>{{ $run->run_code }}</strong>
                            </td>

                            <td style="padding: 12px;">
                                <span style="display: inline-flex; padding: 5px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700;">
                                    {{ ucwords(str_replace('_', ' ', $run->status ?? 'completed')) }}
                                </span>
                            </td>

                            <td style="padding: 12px;">
                                {{ $run->generator?->name ?? 'N/A' }}
                            </td>

                            <td style="padding: 12px;">
                                @if($run->generated_at)
                                    {{ $run->generated_at->timezone(config('app.display_timezone'))->format('F d, Y h:i A') }} PHT
                                @else
                                    N/A
                                @endif
                            </td>

                            <td style="padding: 12px;">
                                {{ $run->results_count }}
                            </td>

                            <td style="padding: 12px; text-align: right;">
                                <a href="{{ route('admin.matching.show', $run) }}" class="btn light">
                                    View Results
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 16px; text-align: center;">
                                <div style="padding: 24px;">
                                    <strong>No matching runs yet.</strong>
                                    <p style="margin-bottom: 0;">
                                        Once eligible children and ready parent profiles are available, click
                                        <strong>Run Matching</strong> to generate the first recommendation batch.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $runs->links() }}
        </div>
    </div>
@endsection
