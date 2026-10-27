<?php

// laravel-app/app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAccountStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(
        Request $request,
        UserAccountStatusService $accountStatusService
    ): RedirectResponse {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role')
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        if ($accountStatusService->deactivateIfDormant($user)) {
            throw ValidationException::withMessages([
                'email' => sprintf(
                    'Your account was made inactive after %d days without a login. Please contact the administrator to reactivate it.',
                    $accountStatusService->inactivityDays()
                ),
            ]);
        }

        if ($user->status === User::STATUS_INACTIVE) {
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ]);
        }

        if (! in_array($user->status, [User::STATUS_ACTIVE, User::STATUS_PENDING], true)) {
            throw ValidationException::withMessages([
                'email' => 'Your account is not available. Please contact the administrator.',
            ]);
        }

        if ($user->status === User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'email' => 'Your account is pending administrator approval.',
            ]);
        }

        if (! $user->role) {
            throw ValidationException::withMessages([
                'email' => 'Your account role is not configured. Please contact the administrator.',
            ]);
        }

        $roleSlug = $user->role->slug;
        $guard = config("auth.role_guards.{$roleSlug}");

        if (! is_string($guard) || $guard === '') {
            throw ValidationException::withMessages([
                'email' => 'Your account role cannot access a system portal. Please contact the administrator.',
            ]);
        }

        $dashboardRoute = config("auth.role_dashboards.{$roleSlug}");
        if (! is_string($dashboardRoute) || $dashboardRoute === '') {
            throw ValidationException::withMessages([
                'email' => 'Your account dashboard is not configured. Please contact the administrator.',
            ]);
        }

        Auth::guard($guard)->login($user, $request->boolean('remember'));
        Auth::shouldUse($guard);
        $request->session()->regenerate();
        $request->session()->forget([
            'pending_parent_registration', 'email_verification_user_id',
            'email_verification_remember', 'email_verification_purpose',
            'email_verification_guard', 'email_verification_challenge',
        ]);
        $request->session()->put('active_auth_guard', $guard);
        $request->session()->put('account_password_hash.'.$guard.'.'.$user->getKey(), $user->password);
        $user->update(['last_login_at' => now()]);

        return redirect()->route($dashboardRoute)->with('success', 'You are now logged in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $allowedGuards = array_values((array) config('auth.role_guards', []));
        $allowedGuards[] = 'web';

        $requestedGuard = (string) $request->input('guard', '');
        $hasExplicitGuard = in_array($requestedGuard, $allowedGuards, true);
        $guard = $hasExplicitGuard ? $requestedGuard : Auth::getDefaultDriver();

        if ($hasExplicitGuard && ! Auth::guard($guard)->check()) {
            return redirect()
                ->route('login')
                ->with('error', 'That portal session has already ended.');
        }

        if (! $hasExplicitGuard && (! in_array($guard, $allowedGuards, true) || ! Auth::guard($guard)->check())) {
            $guard = collect($allowedGuards)
                ->first(fn (string $candidate): bool => Auth::guard($candidate)->check());
        }

        if (is_string($guard)) {
            Auth::guard($guard)->logout();

            if ($request->session()->get('active_auth_guard') === $guard) {
                $request->session()->forget('active_auth_guard');
            }
        }

        // Do not invalidate the shared session. Other portal guards may still
        // be signed in on this browser and must remain intact.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
