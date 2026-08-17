@extends('layouts.dashboard', ['title' => 'Parent Details'])

@section('content')
    @include('admin.parents._styles')

    @php
        $profile = $parent->matchingProfile;

        $averageScore = $profile
            ? round((($profile->financial_capacity_score ?? 0) + ($profile->housing_score ?? 0) + ($profile->parenting_capacity_score ?? 0)) / 3, 1)
            : 0;

        $statusClass = match ($parent->status) {
            'active' => 'badge-green',
            'inactive' => 'badge-gray',
            'suspended' => 'badge-red',
            default => 'badge-yellow',
        };
    @endphp

    <div class="parents-page">
        @if(session('success'))
            <div class="parents-alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="parents-alert-error">{{ session('error') }}</div>
        @endif

        <section class="parents-hero">
            <div class="parents-header">
                <div class="parents-title">
                    <div class="parents-eyebrow">
                        <i class="bi bi-person-vcard"></i>
                        Parent Details
                    </div>

                    <h2>{{ $parent->name }}</h2>

                    <p>
                        Review parent account details, matching profile, adoption cases, and recommendation history.
                    </p>
                </div>

                <div class="parents-actions">
                    <a href="{{ route('admin.parents.edit', $parent) }}" class="btn secondary">
                        <i class="bi bi-pencil-square"></i>
                        Edit
                    </a>

                    <a href="{{ route('admin.parents.index') }}" class="btn light">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </section>

        <section class="parents-stats-grid">
            <div class="parents-stat-card">
                <div class="parents-stat-label">Account Status</div>
                <div style="margin-top: 12px;">
                    <span class="parents-badge {{ $statusClass }}">
                        {{ ucfirst($parent->status) }}
                    </span>
                </div>
                <div class="parents-stat-help">Current parent account status</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">Active Cases</div>
                <div class="parents-stat-value">{{ $activeCasesCount }}</div>
                <div class="parents-stat-help">Cases still in progress</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">Average Score</div>
                <div class="parents-stat-value">{{ $averageScore }}</div>
                <div class="parents-stat-help">Average of readiness scores</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">Home Study</div>
                <div style="margin-top: 12px;">
                    @if($profile?->home_study_verified)
                        <span class="parents-badge badge-green">Verified</span>
                    @else
                        <span class="parents-badge badge-yellow">Pending</span>
                    @endif
                </div>
                <div class="parents-stat-help">Home study verification status</div>
            </div>
        </section>

        <section class="parents-detail-grid">
            <div class="panel">
                <h2>Account Information</h2>

                <div class="parents-info-list">
                    <div class="parents-info-item">
                        <span>Full Name</span>
                        <strong>{{ $parent->name }}</strong>
                    </div>

                    <div class="parents-info-item">
                        <span>Email Address</span>
                        <strong>{{ $parent->email }}</strong>
                    </div>

                    <div class="parents-info-item">
                        <span>Phone Number</span>
                        <strong>{{ $parent->phone_number ?? 'Not provided' }}</strong>
                    </div>

                    <div class="parents-info-item">
                        <span>Role</span>
                        <strong>{{ $parent->role?->name ?? 'Prospective Parent' }}</strong>
                    </div>

                    <div class="parents-info-item">
                        <span>Last Login</span>
                        <strong>{{ $parent->last_login_at?->format('M d, Y h:i A') ?? 'No login recorded' }}</strong>
                    </div>

                    <div class="parents-info-item">
                        <span>Account Created</span>
                        <strong>{{ $parent->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</strong>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Matching Profile</h2>

                @if($profile)
                    <div class="parents-info-list">
                        <div class="parents-info-item">
                            <span>Preferred Child Sex</span>
                            <strong>{{ ucfirst($profile->preferred_child_sex ?? 'Any') }}</strong>
                        </div>

                        <div class="parents-info-item">
                            <span>Preferred Age Range</span>
                            <strong>{{ $profile->min_child_age ?? 'N/A' }} - {{ $profile->max_child_age ?? 'N/A' }} years old</strong>
                        </div>

                        <div class="parents-info-item">
                            <span>Open to Special Needs</span>
                            <strong>{{ $profile->open_to_special_needs ? 'Yes' : 'No' }}</strong>
                        </div>

                        <div class="parents-info-item">
                            <span>Home Study Verified</span>
                            <strong>{{ $profile->home_study_verified ? 'Yes' : 'No' }}</strong>
                        </div>

                        <div class="parents-info-item">
                            <span>Matching Notes</span>
                            <strong>{{ $profile->matching_notes ?? 'No notes recorded' }}</strong>
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        No matching profile has been created yet.
                    </div>
                @endif
            </div>
        </section>

        @if($profile)
            <section class="panel">
                <h2>Readiness Scores</h2>
                <p class="muted">Scores range from 0 to 100; a higher score means better assessment performance and results.</p>

                <div class="parents-score-grid">
                    <div class="parents-score-box">
                        <div class="muted">Financial Capacity</div>
                        <div class="parents-score-value">{{ $profile->financial_capacity_score }} / 100</div>
                    </div>

                    <div class="parents-score-box">
                        <div class="muted">Housing Readiness</div>
                        <div class="parents-score-value">{{ $profile->housing_score }} / 100</div>
                    </div>

                    <div class="parents-score-box">
                        <div class="muted">Parenting Capacity</div>
                        <div class="parents-score-value">{{ $profile->parenting_capacity_score }} / 100</div>
                    </div>
                </div>
            </section>
        @endif

        <section class="panel">
            <h2>Adoption Cases</h2>

            <div class="parents-table-wrap">
                <table class="parents-table">
                    <thead>
                        <tr>
                            <th>Case Code</th>
                            <th>Child</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Documents</th>
                            <th>Opened</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($adoptionCases as $case)
                            <tr>
                                <td>
                                    <strong>{{ $case->case_code }}</strong>
                                </td>

                                <td>
                                    {{ $case->child?->full_name ?? 'N/A' }}
                                </td>

                                <td>
                                    <span class="parents-badge badge-blue">
                                        {{ $case->status_label }}
                                    </span>
                                </td>

                                <td>
                                    {{ $case->assignedSocialWorker?->name ?? 'Not assigned' }}
                                </td>

                                <td>
                                    {{ $case->document_progress }}
                                </td>

                                <td>
                                    {{ $case->opened_at?->format('M d, Y') ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        No adoption case has been created for this parent yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Recent Matching Recommendations</h2>

            <div class="parents-table-wrap">
                <table class="parents-table">
                    <thead>
                        <tr>
                            <th>Run</th>
                            <th>Child</th>
                            <th>Scores</th>
                            <th>Ranks</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($matchingResults as $result)
                            <tr>
                                <td>
                                    <strong>{{ $result->run?->run_code ?? 'N/A' }}</strong>
                                    <div class="muted">{{ $result->created_at?->format('M d, Y h:i A') }}</div>
                                </td>

                                <td>
                                    {{ $result->child?->full_name ?? 'N/A' }}
                                    <div class="muted">{{ $result->child?->child_code ?? 'No code' }}</div>
                                </td>

                                <td>
                                    Child: <strong>{{ $result->child_score }}</strong>
                                    <br>
                                    Parent: <strong>{{ $result->parent_score }}</strong>
                                </td>

                                <td>
                                    Child Rank: <strong>{{ $result->rank_for_child ?? 'N/A' }}</strong>
                                    <br>
                                    Parent Rank: <strong>{{ $result->rank_for_parent ?? 'N/A' }}</strong>
                                </td>

                                <td>
                                    <span class="parents-badge badge-blue">
                                        {{ ucwords(str_replace('_', ' ', $result->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        No matching recommendation history yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
