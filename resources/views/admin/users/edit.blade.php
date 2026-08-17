@extends('layouts.dashboard', ['title' => 'Edit User Account'])

@section('content')
    <div class="panel">
        <div class="panel-header-row">
            <div>
                <h2>Edit User Account</h2>
                <p>Update {{ $user->name }}'s account information and access status.</p>
            </div>
            <a href="{{ route('admin.users.show', $user) }}" class="btn light">Back to User</a>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select name="role_id" id="role_id" class="form-control" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="150" required>
                    @error('name')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" maxlength="150" required>
                    @error('email')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ old('phone_number', $user->phone_number) }}" maxlength="30">
                    @error('phone_number')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="password">New Password <span style="color:#667085; font-weight:400;">(optional)</span></label>
                    <input type="password" name="password" id="password" class="form-control" minlength="8" maxlength="255" autocomplete="new-password">
                    @error('password')<small class="form-error">{{ $message }}</small>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="8" maxlength="255" autocomplete="new-password">
                </div>
            </div>

            <button type="button" class="btn light" id="userPasswordToggle" aria-pressed="false" style="margin-top: 12px;">
                <i class="bi bi-eye"></i><span>Show Passwords</span>
            </button>

            <div class="form-actions" style="margin-top: 24px;">
                <button type="submit" class="btn">Save Changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn light">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('userPasswordToggle');
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');

            toggle?.addEventListener('click', function () {
                const showing = password?.type === 'text';
                const nextType = showing ? 'password' : 'text';
                if (password) password.type = nextType;
                if (confirmation) confirmation.type = nextType;
                toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
                toggle.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
                toggle.querySelector('span').textContent = showing ? 'Show Passwords' : 'Hide Passwords';
            });
        });
    </script>
@endsection
