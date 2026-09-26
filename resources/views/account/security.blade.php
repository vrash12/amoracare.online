@extends('layouts.dashboard', ['title' => 'Account Security'])

@section('content')
<section class="panel account-panel">
    <h1>Change password</h1>
    @if(auth()->user()->must_change_password)
        <div class="alert alert-error" role="status">Your administrator provided a temporary password. Choose your own password before using your account.</div>
    @endif
    <p>Enter your current password and choose a new password that only you know. Use at least 8 characters.</p>
    <form method="POST" action="{{ route(auth()->user()->accountRoute('password')) }}" class="account-form">
        @csrf
        @method('PUT')
        <div><label for="current_password">Current or temporary password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div><label for="new_password">New password</label><input id="new_password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="255" required></div>
        <div><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="255" required></div>
        <button class="btn" type="submit">Save new password</button>
    </form>
</section>
@endsection
