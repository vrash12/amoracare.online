<!-- resources/views/admin/dashboard.blade.php -->
@extends('layouts.dashboard', ['title' => 'Admin Dashboard'])

@section('content')
    <div class="cards">
        <div class="card">
            <div class="card-title">Registered Users</div>
            <div class="card-value">{{ number_format($registeredUsersCount) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Adoption Cases</div>
            <div class="card-value">{{ number_format($adoptionCasesCount) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Pending Documents</div>
            <div class="card-value">{{ number_format($pendingDocumentsCount) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Donation Records</div>
            <div class="card-value">{{ number_format($donationRecordsCount) }}</div>
        </div>
    </div>

    <div class="panel">
        <h2>System Overview</h2>
        <p>
            Welcome to the AmoraCare administrator dashboard. From here, authorized staff can manage users,
            adoption cases, child records, donations, documents, reports, AI legal guidance logs, and audit logs.
        </p>
    </div>

    <div class="panel">
        <h2>Quick Actions</h2>

        <div class="quick-actions">
            <a href="{{ route('admin.users.index') }}" class="btn">Manage Users</a>
            <a href="{{ route('admin.adoption-cases.create') }}" class="btn secondary">Create Adoption Case</a>
            <a href="{{ route('admin.donations.create') }}" class="btn secondary">Record Donation</a>
            <a href="{{ route('admin.reports.index') }}" class="btn light">Generate Report</a>
        </div>
    </div>

    <div class="panel">
        <h2>Security Reminder</h2>
        <p>
            Child records, parent applications, donor records, and adoption case data are confidential.
            Access must always follow role-based authorization and audit logging rules.
        </p>
    </div>
@endsection
