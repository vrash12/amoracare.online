@extends('layouts.dashboard', ['title' => 'User Management'])

@section('content')
    @php
        $queryParams = request()->except('page');

        $statusOptions = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
        ];

        $currentSearch = request('search');
        $currentRole = request('role');
        $currentStatus = request('status');

        $roles = $roles ?? \App\Models\Role::orderBy('name')->get();

        $totalUsersShown = method_exists($users, 'total') ? $users->total() : $users->count();

        $visibleUsers = method_exists($users, 'getCollection')
            ? $users->getCollection()
            : collect($users);

        $activeUsersCount = $visibleUsers->where('status', 'active')->count();
        $inactiveUsersCount = $visibleUsers->where('status', 'inactive')->count();
        $pendingUsersCount = $visibleUsers->where('status', 'pending')->count();

        $statusBadgeClass = function ($status) {
            return match ($status) {
                'active' => 'badge-green',
                'inactive' => 'badge-gray',
                'pending' => 'badge-yellow',
                default => 'badge-yellow',
            };
        };

        $roleBadgeClass = function ($slug) {
            return match ($slug) {
                'admin' => 'badge-red',
                'staff' => 'badge-green',
                'social_worker' => 'badge-green',
                'prospective_parent' => 'badge-blue',
                'external_reviewer' => 'badge-purple',
                default => 'badge-gray',
            };
        };

        $getInitials = function ($name) {
            if (!$name) {
                return 'U';
            }

            $parts = collect(explode(' ', trim($name)))
                ->filter()
                ->values();

            if ($parts->count() === 1) {
                return strtoupper(substr($parts[0], 0, 1));
            }

            return strtoupper(substr($parts[0], 0, 1) . substr($parts[$parts->count() - 1], 0, 1));
        };
    @endphp

    <style>
        .users-page {
            display: grid;
            gap: 18px;
        }

        .users-hero {
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 60%, #f8fafc 100%);
            border: 1px solid #fed7aa;
            border-radius: 22px;
            padding: 22px;
        }

        .users-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .users-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #ffedd5;
            color: #9a3412;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .users-title h2 {
            margin: 0;
            font-size: 28px;
            color: #111827;
        }

        .users-title p {
            margin: 8px 0 0;
            color: #667085;
        }

        .users-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .users-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .users-stat-card {
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
        }

        .users-stat-label {
            font-size: 13px;
            color: #667085;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 8px;
        }

        .users-stat-value {
            font-size: 32px;
            font-weight: 900;
            color: #111827;
            line-height: 1;
        }

        .users-stat-help {
            margin-top: 6px;
            color: #667085;
            font-size: 13px;
        }

        .users-filter-form {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) 220px 220px auto auto;
            gap: 12px;
            align-items: end;
        }

        .users-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 13px;
            color: #344054;
        }

        .users-field input,
        .users-field select {
            width: 100%;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 42px;
            background: #ffffff;
        }

        .users-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1050px;
            background: #ffffff;
        }

        .users-table thead th {
            background: #f9fafb;
            color: #475467;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .users-table tbody td {
            padding: 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .users-table tbody tr:hover {
            background: #fcfcfd;
        }

        .users-table tbody tr:last-child td {
            border-bottom: none;
        }

        .user-person {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
            display: grid;
            place-items: center;
            font-weight: 900;
            border: 1px solid #fed7aa;
            flex: 0 0 auto;
        }

        .user-name {
            font-weight: 900;
            color: #111827;
        }

        .muted {
            color: #667085;
            font-size: 12px;
        }

        .users-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-green {
            background: #ecfdf3;
            color: #047857;
        }

        .badge-yellow {
            background: #fffbeb;
            color: #b45309;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #4b5563;
        }

        .badge-red {
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge-purple {
            background: #f5f3ff;
            color: #6d28d9;
        }

        .users-action-row {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 38px 16px;
            color: #667085;
        }

        .amora-pagination {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
        }

        .amora-pagination-info {
            color: #667085;
            font-size: 13px;
        }

        .amora-pagination-links {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .amora-page-link,
        .amora-page-disabled,
        .amora-page-active {
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid #e5e7eb;
        }

        .amora-page-link {
            background: #ffffff;
            color: #344054;
        }

        .amora-page-link:hover {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #9a3412;
        }

        .amora-page-active {
            background: #9a3412;
            color: #ffffff;
            border-color: #9a3412;
        }

        .amora-page-disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .users-alert-success,
        .users-alert-error {
            padding: 14px 16px;
            border-radius: 16px;
            font-weight: 700;
        }

        .users-alert-success {
            background: #ecfdf3;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .users-alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1100px) {
            .users-filter-form {
                grid-template-columns: 1fr 1fr;
            }

            .users-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .users-title h2 {
                font-size: 24px;
            }

            .users-filter-form,
            .users-stats-grid {
                grid-template-columns: 1fr;
            }

            .users-actions,
            .users-actions .btn,
            .users-actions a {
                width: 100%;
            }

            .amora-pagination {
                align-items: stretch;
            }

            .amora-pagination-links {
                width: 100%;
            }

            .amora-page-link,
            .amora-page-disabled,
            .amora-page-active {
                flex: 1;
            }
        }
    </style>

    <div class="users-page">
        @if(session('success'))
            <div class="users-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="users-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <section class="users-hero">
            <div class="users-header">
                <div class="users-title">
                    <div class="users-eyebrow">
                        <i class="bi bi-people"></i>
                        Admin Access Control
                    </div>

                
                </div>

                <div class="users-actions">
                    <a href="{{ route('admin.users.create') }}" class="btn secondary">
                        <i class="bi bi-plus-circle"></i>
                        Add User
                    </a>
                </div>
            </div>
        </section>

        <section class="users-stats-grid">
            <div class="users-stat-card">
                <div class="users-stat-label">Total Results</div>
                <div class="users-stat-value">{{ $totalUsersShown }}</div>
                <div class="users-stat-help">Users matching the current filter</div>
            </div>

            <div class="users-stat-card">
                <div class="users-stat-label">Active On This Page</div>
                <div class="users-stat-value">{{ $activeUsersCount }}</div>
                <div class="users-stat-help">Visible active accounts</div>
            </div>

            <div class="users-stat-card">
                <div class="users-stat-label">Inactive On This Page</div>
                <div class="users-stat-value">{{ $inactiveUsersCount }}</div>
                <div class="users-stat-help">Visible inactive accounts</div>
            </div>

            <div class="users-stat-card">
                <div class="users-stat-label">Pending On This Page</div>
                <div class="users-stat-value">{{ $pendingUsersCount }}</div>
                <div class="users-stat-help">Visible pending accounts</div>
            </div>
        </section>

        <section class="panel">
            <form method="GET" action="{{ route('admin.users.index') }}" class="users-filter-form">
                <div class="users-field">
                    <label for="search">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Search by name, email, or phone"
                    >
                </div>

                <div class="users-field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" @selected($currentRole === $role->slug)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="users-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($currentStatus === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn secondary">
                    <i class="bi bi-funnel"></i>
                    Filter
                </button>

                <a href="{{ route('admin.users.index') }}" class="btn light">
                    Clear
                </a>
            </form>
        </section>

        <section class="panel">
            <div class="users-table-wrap">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $user)
                            @php
                                $roleSlug = $user->role?->slug;
                                $roleName = $user->role?->name ?? 'No Role';
                                $statusClass = $statusBadgeClass($user->status);
                                $roleClass = $roleBadgeClass($roleSlug);
                            @endphp

                            <tr>
                                <td>
                                    <div class="user-person">
                                        <div class="user-avatar">
                                            {{ $getInitials($user->name) }}
                                        </div>

                                        <div>
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="muted">
                                                Created {{ $user->created_at?->format('M d, Y') ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    {{ $user->email }}
                                </td>

                                <td>
                                    {{ $user->phone_number ?? 'Not provided' }}
                                </td>

                                <td>
                                    <span class="users-badge {{ $roleClass }}">
                                        {{ $roleName }}
                                    </span>
                                </td>

                                <td>
                                    <span class="users-badge {{ $statusClass }}">
                                        {{ ucfirst($user->status ?? 'N/A') }}
                                    </span>
                                </td>

                                <td>
                                    {{ $user->last_login_at?->format('M d, Y h:i A') ?? 'Never' }}
                                </td>

                                <td>
                                    <div class="users-action-row">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn light">
                                            View
                                        </a>

                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn secondary">
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Delete this user account?');"
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
                                        <strong>No users found.</strong>
                                        <br>
                                        Try changing your filters or add a new user account.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($users, 'hasPages') && $users->hasPages())
                <div class="amora-pagination">
                    <div class="amora-pagination-info">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
                    </div>

                    <div class="amora-pagination-links">
                        @if($users->onFirstPage())
                            <span class="amora-page-disabled">Previous</span>
                        @else
                            <a
                                href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $users->currentPage() - 1])) }}"
                                class="amora-page-link"
                            >
                                Previous
                            </a>
                        @endif

                        @php
                            $start = max(1, $users->currentPage() - 2);
                            $end = min($users->lastPage(), $users->currentPage() + 2);
                        @endphp

                        @if($start > 1)
                            <a
                                href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => 1])) }}"
                                class="amora-page-link"
                            >
                                1
                            </a>

                            @if($start > 2)
                                <span class="amora-page-disabled">...</span>
                            @endif
                        @endif

                        @for($page = $start; $page <= $end; $page++)
                            @if($page === $users->currentPage())
                                <span class="amora-page-active">{{ $page }}</span>
                            @else
                                <a
                                    href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $page])) }}"
                                    class="amora-page-link"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        @if($end < $users->lastPage())
                            @if($end < $users->lastPage() - 1)
                                <span class="amora-page-disabled">...</span>
                            @endif

                            <a
                                href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $users->lastPage()])) }}"
                                class="amora-page-link"
                            >
                                {{ $users->lastPage() }}
                            </a>
                        @endif

                        @if($users->hasMorePages())
                            <a
                                href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['page' => $users->currentPage() + 1])) }}"
                                class="amora-page-link"
                            >
                                Next
                            </a>
                        @else
                            <span class="amora-page-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="amora-pagination">
                    <div class="amora-pagination-info">
                        Showing {{ $visibleUsers->count() }} result{{ $visibleUsers->count() === 1 ? '' : 's' }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
