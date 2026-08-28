@extends('layouts.dashboard', ['title' => 'View User'])

@section('content')
    <div class="panel">
        <div class="panel-header-row">
            <div>
                <h2>{{ $user->name }}</h2>
                <p>Review account identity, role, status, and recent access information.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn secondary"><i class="bi bi-pencil-square"></i> Edit User</a>
                <a href="{{ route('admin.users.index') }}" class="btn light"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Role</div>
            <div class="card-value" style="font-size: 22px;">{{ $user->role?->name ?? 'No role assigned' }}</div>
        </div>
        <div class="card">
            <div class="card-title">Status</div>
            <div class="card-value" style="font-size: 22px;">{{ ucfirst($user->status) }}</div>
        </div>
        <div class="card">
            <div class="card-title">Created</div>
            <div class="card-value" style="font-size: 18px;">{{ $user->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
        </div>
        <div class="card">
            <div class="card-title">Last Login</div>
            <div class="card-value" style="font-size: 18px;">{{ $user->last_login_at?->format('M d, Y h:i A') ?? 'Never' }}</div>
        </div>
        <div class="card">
            <div class="card-title">Automatic Inactivity</div>
            <div class="card-value" style="font-size: 18px;">
                @if($user->status === 'active' && $user->inactivityDeadline())
                    {{ $user->inactivityDeadline()->format('M d, Y h:i A') }}
                @elseif($user->status === 'pending')
                    Starts after activation
                @else
                    Login currently disabled
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-title">Email Verification</div>
            <div class="card-value" style="font-size: 18px;">
                {{ $user->email_verified_at?->format('M d, Y h:i A') ?? 'Verification required' }}
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Contact Information</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Email Address</label>
                <div class="form-control" style="height: auto;">{{ $user->email }}</div>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <div class="form-control" style="height: auto;">{{ $user->phone_number ?? 'Not provided' }}</div>
            </div>
        </div>
    </div>
@endsection
