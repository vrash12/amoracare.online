@extends('layouts.dashboard', ['title' => 'Create User Account'])

@section('content')
    <div class="panel">
        <div class="panel-header-row">
            <div>
                <h2>Create User Account</h2>
                <p>Add a new user and assign a system role.</p>
            </div>

            <a href="{{ route('admin.users.index') }}" class="btn light">
                Back to Users
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>

                <ul style="margin-top: 10px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select
                        name="role_id"
                        id="role_id"
                        class="form-control"
                        required
                    >
                        <option value="">Select role</option>

                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('role_id')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select
                        name="status"
                        id="status"
                        class="form-control"
                        required
                    >
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                    </select>

                    @error('status')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        maxlength="150"
                        required
                    >

                    @error('name')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        maxlength="150"
                        required
                    >

                    @error('email')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input
                        type="text"
                        name="phone_number"
                        id="phone_number"
                        class="form-control"
                        value="{{ old('phone_number') }}"
                        maxlength="30"
                    >

                    @error('phone_number')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        minlength="8"
                        required
                    >

                    @error('password')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="form-control"
                        minlength="8"
                        required
                    >
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px;">
                <button type="submit" class="btn">
                    Create User
                </button>

                <a href="{{ route('admin.users.index') }}" class="btn light">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection