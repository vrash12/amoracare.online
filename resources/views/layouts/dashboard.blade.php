<!DOCTYPE html>
<!-- laravel-app/resources/views/layouts/dashboard.blade.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'AmoraCare Dashboard' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Main Dashboard CSS --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    {{-- Bootstrap Icons --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >
</head>

@php
    $user = auth()->user();
    $roleSlug = $user?->role?->slug ?? 'guest';

    $roleClass = match ($roleSlug) {
        'admin' => 'role-admin',
        'prospective_parent' => 'role-parent',
        'external_reviewer' => 'role-reviewer',
        default => 'role-guest',
    };

    $dashboardRoute = match ($roleSlug) {
        'admin' => route('admin.dashboard'),
        'prospective_parent' => route('parent.dashboard'),
        'external_reviewer' => route('reviewer.dashboard'),
        default => route('dashboard'),
    };

    $portalLabel = match ($roleSlug) {
        'admin' => 'Admin Panel',
        'prospective_parent' => 'Parent Portal',
        'external_reviewer' => 'RACCO Reviewer Portal',
        default => 'Dashboard',
    };
@endphp

<body class="dashboard-body {{ $roleClass }}">

<div class="dashboard-shell" id="dashboardShell">
    <aside class="dashboard-sidebar" aria-label="Dashboard sidebar">
        <div class="sidebar-top-row">
            <button
                type="button"
                class="sidebar-collapse-button"
                id="topbarSidebarToggle"
                title="Collapse sidebar"
                aria-label="Toggle sidebar"
                aria-expanded="true"
            >
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <div class="sidebar-top-text">
                <strong>AmoraCare</strong>
                <span>{{ $portalLabel }}</span>
            </div>
        </div>

        {{-- Logo appears only when sidebar is minimized --}}
        <button
            type="button"
            class="sidebar-mini-logo-button"
            id="sidebarMiniLogoButton"
            title="Expand sidebar"
            aria-label="Expand sidebar"
        >
            <img src="{{ asset('images/amora.png') }}" alt="AmoraCare Logo">
        </button>

        @auth
            <nav class="sidebar-nav">
                {{-- ADMIN MENU --}}
                @if($roleSlug === 'admin')
                    <div class="nav-label">Admin Menu</div>

                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-grid-1x2"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-people"></i>
                        </span>
                        <span class="nav-text">User Management</span>
                    </a>

                    <a href="{{ route('admin.children.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.children.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-person-hearts"></i>
                        </span>
                        <span class="nav-text">Child Profiles</span>
                    </a>

                    <a href="{{ route('admin.parents.index') }}"
   class="sidebar-link {{ request()->routeIs('admin.parents.*') ? 'active' : '' }}">
    <span class="nav-icon">
        <i class="bi bi-person-lines-fill"></i>
    </span>
    <span class="nav-text">Parent Profiles</span>
</a>

                    <a href="{{ route('admin.adoption-cases.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.adoption-cases.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-folder2-open"></i>
                        </span>
                        <span class="nav-text">Adoption Cases</span>
                    </a>

                    <a href="{{ route('admin.matching.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.matching.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-diagram-3"></i>
                        </span>
                        <span class="nav-text">Matching</span>
                    </a>

                    <a href="{{ route('admin.donations.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.donations.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-gift"></i>
                        </span>
                        <span class="nav-text">Donations</span>
                    </a>

                    <a href="{{ route('admin.reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </span>
                        <span class="nav-text">Reports</span>
                    </a>

                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        <span class="nav-text">Audit Logs</span>
                    </a>

                @endif

                {{-- PROSPECTIVE PARENT MENU --}}
                @if($roleSlug === 'prospective_parent')
                    <div class="nav-label">Applicant Menu</div>

                    <a href="{{ route('parent.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-grid-1x2"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <a href="{{ route('parent.application.index') }}"
                       class="sidebar-link {{ request()->routeIs('parent.application.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-file-earmark-person"></i>
                        </span>
                        <span class="nav-text">My Application</span>
                    </a>

                    <a href="{{ route('parent.documents.index') }}"
                       class="sidebar-link {{ request()->routeIs('parent.documents.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </span>
                        <span class="nav-text">Required Documents</span>
                    </a>

                    <a href="{{ route('parent.ai.index') }}"
                       class="sidebar-link {{ request()->routeIs('parent.ai.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-chat-square-text"></i>
                        </span>
                        <span class="nav-text">AI Legal Guidance</span>
                    </a>
                @endif

                {{-- RACCO / EXTERNAL REVIEWER MENU --}}
                @if($roleSlug === 'external_reviewer')
                    <div class="nav-label">RACCO Reviewer Menu</div>

                    <a href="{{ route('reviewer.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('reviewer.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-grid-1x2"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <a href="{{ route('reviewer.cases.index') }}"
                       class="sidebar-link {{ request()->routeIs('reviewer.cases.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <i class="bi bi-clipboard-data"></i>
                        </span>
                        <span class="nav-text">Authorized Cases</span>
                    </a>
                @endif
            </nav>

            {{-- Profile and logout grouped at the bottom --}}
            <div class="sidebar-bottom">
                <div class="role-card">
                    <div class="role-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="role-card-text">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->role?->name }}</span>
                    </div>
                </div>

                <div class="sidebar-footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="logout-button">
                            <span class="nav-icon">
                                <i class="bi bi-box-arrow-right"></i>
                            </span>
                            <span class="nav-text">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="topbar-left">
                <div>
                    <h2>{{ $title ?? 'Dashboard' }}</h2>
                </div>
            </div>

            @auth
                <div class="topbar-actions">
                    <div class="topbar-user">
                        <strong>{{ auth()->user()->name ?? 'Guest' }}</strong>
                    </div>
                </div>
            @endauth
        </header>

        <section class="dashboard-content">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <strong>Please check the following:</strong>
                    <ul style="margin: 8px 0 0 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-auto-capitalize="words"]').forEach(function (input) {
            input.addEventListener('blur', function () {
                input.value = input.value
                    .trim()
                    .replace(/\s+/g, ' ')
                    .toLocaleLowerCase()
                    .replace(/(^|[\s'-])\p{L}/gu, function (match) {
                        return match.toLocaleUpperCase();
                    });
            });
        });

        const dashboardShell = document.getElementById('dashboardShell');
        const sidebarToggle = document.getElementById('topbarSidebarToggle');
        const sidebarMiniLogoButton = document.getElementById('sidebarMiniLogoButton');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        if (!dashboardShell || !sidebarToggle) {
            return;
        }

        const storageKey = 'amoracare_sidebar_collapsed';
        const toggleIconMarkup = '<i class="bi bi-layout-sidebar-inset"></i>';

        function isMobileScreen() {
            return window.innerWidth <= 768;
        }

        function collapseSidebar() {
            dashboardShell.classList.add('sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', 'false');
            sidebarToggle.title = 'Expand sidebar';
            sidebarToggle.innerHTML = toggleIconMarkup;
        }

        function expandSidebar() {
            dashboardShell.classList.remove('sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', 'true');
            sidebarToggle.title = 'Collapse sidebar';
            sidebarToggle.innerHTML = toggleIconMarkup;
        }

        function saveState() {
            localStorage.setItem(
                storageKey,
                dashboardShell.classList.contains('sidebar-collapsed') ? 'true' : 'false'
            );
        }

        function initializeSidebar() {
            if (isMobileScreen()) {
                collapseSidebar();
                return;
            }

            const savedState = localStorage.getItem(storageKey);

            if (savedState === 'true') {
                collapseSidebar();
            } else {
                expandSidebar();
            }
        }

        sidebarToggle.addEventListener('click', function () {
            if (dashboardShell.classList.contains('sidebar-collapsed')) {
                expandSidebar();
            } else {
                collapseSidebar();
            }

            saveState();
        });

        if (sidebarMiniLogoButton) {
            sidebarMiniLogoButton.addEventListener('click', function () {
                expandSidebar();
                saveState();
            });
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function () {
                collapseSidebar();
                saveState();
            });
        }

        window.addEventListener('resize', function () {
            initializeSidebar();
        });

        initializeSidebar();
    });
</script>

</body>
</html>
