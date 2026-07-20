<?php
// laravel-app/app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | One-time safety fix
        |--------------------------------------------------------------------------
        | This prevents errors like:
        | Attempt to read property "role" on true
        |
        | Auth::user() should return a User model, but if something returns true,
        | we safely log out instead of trying to read $user->role.
        */
        if (!$authUser instanceof User) {
            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Please log in again.');
        }

        $user = $authUser->loadMissing('role');

        if ($user->status !== 'active') {
            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account is not active. Please contact the administrator.');
        }

        if (!$user->role) {
            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account role is not properly configured.');
        }

        $roleSlug = $user->role->slug;

        if ($roleSlug === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($roleSlug === 'prospective_parent') {
            return redirect()->route('parent.dashboard');
        }

        if ($roleSlug === 'external_reviewer') {
            return redirect()->route('reviewer.dashboard');
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', 'Unauthorized role. Please contact the administrator.');
    }
}