<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function security(): View
    {
        return view('account.security');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:'.Auth::getDefaultDriver()],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Choose a new password different from your current password.']);
        }

        // Only the authenticated account can change its password. Never use an ID supplied by the form.
        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        // Refresh only this session's password fingerprint. Other sessions with
        // the previous password must still sign in again.
        $request->session()->put(
            'account_password_hash.'.Auth::getDefaultDriver().'.'.$user->getKey(),
            $user->password
        );

        // Keep the other portal guards in this shared browser session intact.
        $request->session()->regenerateToken();

        return redirect()->route($user->hasAcceptedCurrentTerms()
            ? $user->accountRoute('security') : $user->accountRoute('terms'))
            ->with('success', 'Your password has been changed successfully.');
    }

    public function terms(): View
    {
        return view('account.terms');
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $request->validate([
            'terms_accepted' => ['accepted'],
            'terms_version' => ['required', Rule::in([(string) config('legal.terms_version')])],
        ], ['terms_version.in' => 'The terms have changed. Reload this page and review the current terms.']);

        $user = $request->user();
        $user->forceFill([
            'terms_accepted_version' => (string) config('legal.terms_version'),
            'terms_accepted_at' => now(),
        ])->save();

        return redirect()->route($user->must_change_password
            ? $user->accountRoute('security') : config('auth.role_dashboards.'.$user->role->slug))
            ->with('success', 'Your acceptance of the Terms and Conditions has been recorded.');
    }
}
