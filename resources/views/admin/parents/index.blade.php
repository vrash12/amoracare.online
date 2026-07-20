@extends('layouts.dashboard', ['title' => 'Parent Profiles'])

@section('content')
    @include('admin.parents._styles')

    @php
        $queryParams = request()->except('page');
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
                        <i class="bi bi-people"></i>
                        Prospective Parent Records
                    </div>

                    <h2>Parent Profiles</h2>

                    <p>
                        Manage prospective parent accounts, matching preferences, home study status, and readiness scores.
                    </p>
                </div>

                <div class="parents-actions">
                    <a href="{{ route('admin.parents.create') }}" class="btn secondary">
                        <i class="bi bi-plus-circle"></i>
                        Add Parent
                    </a>
                </div>
            </div>
        </section>

        <section class="parents-stats-grid">
            <div class="parents-stat-card">
                <div class="parents-stat-label">Total Parents</div>
                <div class="parents-stat-value">{{ $totalParentsCount }}</div>
                <div class="parents-stat-help">All prospective parent accounts</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">Active Parents</div>
                <div class="parents-stat-value">{{ $activeParentsCount }}</div>
                <div class="parents-stat-help">Currently active accounts</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">With Matching Profile</div>
                <div class="parents-stat-value">{{ $withProfileCount }}</div>
                <div class="parents-stat-help">Ready for matching evaluation</div>
            </div>

            <div class="parents-stat-card">
                <div class="parents-stat-label">Home Study Verified</div>
                <div class="parents-stat-value">{{ $homeStudyVerifiedCount }}</div>
                <div class="parents-stat-help">Verified parent readiness</div>
            </div>
        </section>

        <section class="panel">
            <form method="GET" action="{{ route('admin.parents.index') }}" class="parents-filter-form">
                <div class="parents-field">
                    <label for="search">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search name, email, or phone"
                    >
                </div>

                <div class="parents-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($status ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="parents-field">
                    <label for="home_study_verified">Home Study</label>
                    <select id="home_study_verified" name="home_study_verified">
                        <option value="">All</option>
                        <option value="1" @selected(($homeStudyVerified ?? '') === '1')>Verified</option>
                        <option value="0" @selected(($homeStudyVerified ?? '') === '0')>Not Verified</option>
                    </select>
                </div>

                <div class="parents-field">
                    <label for="open_to_special_needs">Special Needs</label>
                    <select id="open_to_special_needs" name="open_to_special_needs">
                        <option value="">All</option>
                        <option value="1" @selected(($openToSpecialNeeds ?? '') === '1')>Open</option>
                        <option value="0" @selected(($openToSpecialNeeds ?? '') === '0')>Not Open</option>
                    </select>
                </div>

                <button type="submit" class="btn secondary">
                    <i class="bi bi-funnel"></i>
                    Filter
                </button>

                <a href="{{ route('admin.parents.index') }}" class="btn light">
                    Clear
                </a>
            </form>
        </section>

        <section class="panel">
            <div class="parents-table-wrap">
                <table class="parents-table">
                    <thead>
                        <tr>
                            <th>Parent</th>
                            <th>Status</th>
                            <th>Preferences</th>
                            <th>Home Study</th>
                            <th>Scores</th>
                            <th>Cases</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($parents as $parent)
                            @php
                                $profile = $parent->matchingProfile;

                                $statusClass = match ($parent->status) {
                                    'active' => 'badge-green',
                                    'inactive' => 'badge-gray',
                                    'suspended' => 'badge-red',
                                    default => 'badge-yellow',
                                };

                                $averageScore = $profile
                                    ? round((($profile->financial_capacity_score ?? 0) + ($profile->housing_score ?? 0) + ($profile->parenting_capacity_score ?? 0)) / 3, 1)
                                    : 0;
                            @endphp

                            <tr>
                                <td>
                                    <div class="parent-person">
                                        <div class="parent-avatar">
                                            {{ strtoupper(substr($parent->name, 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="parent-name">{{ $parent->name }}</div>
                                            <div class="muted">{{ $parent->email }}</div>
                                            <div class="muted">{{ $parent->phone_number ?? 'No phone number' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="parents-badge {{ $statusClass }}">
                                        {{ ucfirst($parent->status) }}
                                    </span>
                                </td>

                                <td>
                                    @if($profile)
                                        <strong>{{ ucfirst($profile->preferred_child_sex ?? 'Any') }}</strong>
                                        <div class="muted">
                                            Age {{ $profile->min_child_age ?? 'N/A' }} - {{ $profile->max_child_age ?? 'N/A' }}
                                        </div>

                                        <div style="margin-top: 6px;">
                                            @if($profile->open_to_special_needs)
                                                <span class="parents-badge badge-blue">Open to Special Needs</span>
                                            @else
                                                <span class="parents-badge badge-gray">Not Open to Special Needs</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="parents-badge badge-yellow">No Matching Profile</span>
                                    @endif
                                </td>

                                <td>
                                    @if($profile?->home_study_verified)
                                        <span class="parents-badge badge-green">Verified</span>
                                    @else
                                        <span class="parents-badge badge-yellow">Pending</span>
                                    @endif
                                </td>

                                <td>
                                    @if($profile)
                                        <strong>{{ $averageScore }} / 100</strong>
                                        <div class="muted">Average readiness score</div>
                                        <div class="muted">
                                            F: {{ $profile->financial_capacity_score }},
                                            H: {{ $profile->housing_score }},
                                            P: {{ $profile->parenting_capacity_score }}
                                        </div>
                                    @else
                                        <span class="muted">No scores yet</span>
                                    @endif
                                </td>

                                <td>
                                    <strong>{{ $parent->active_cases_count }}</strong>
                                    <div class="muted">Active cases</div>
                                    <div class="muted">{{ $parent->total_cases_count }} total cases</div>
                                </td>

                                <td>
                                    <div class="parents-action-row">
                                        <a href="{{ route('admin.parents.show', $parent) }}" class="btn light">
                                            View
                                        </a>

                                        <a href="{{ route('admin.parents.edit', $parent) }}" class="btn secondary">
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.parents.destroy', $parent) }}"
                                            onsubmit="return confirm('Delete this parent account? This is not allowed if the parent has active cases.');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn light">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <strong>No parent profiles found.</strong>
                                        <br>
                                        Try changing your filters or add a new parent profile.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($parents->hasPages())
                <div class="parents-pagination">
                    <div class="parents-pagination-info">
                        Showing {{ $parents->firstItem() }} to {{ $parents->lastItem() }} of {{ $parents->total() }} results
                    </div>

                    <div class="parents-pagination-links">
                        @if($parents->onFirstPage())
                            <span class="parents-page-disabled">Previous</span>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $parents->currentPage() - 1])) }}" class="parents-page-link">
                                Previous
                            </a>
                        @endif

                        @php
                            $start = max(1, $parents->currentPage() - 2);
                            $end = min($parents->lastPage(), $parents->currentPage() + 2);
                        @endphp

                        @for($page = $start; $page <= $end; $page++)
                            @if($page === $parents->currentPage())
                                <span class="parents-page-active">{{ $page }}</span>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $page])) }}" class="parents-page-link">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        @if($parents->hasMorePages())
                            <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $parents->currentPage() + 1])) }}" class="parents-page-link">
                                Next
                            </a>
                        @else
                            <span class="parents-page-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection