<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserAccountStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        UserAccountStatusService $accountStatusService
    ): RedirectResponse {
        $guards = array_values((array) config('auth.role_guards', []));
        $guards[] = 'web';
        $guards = array_values(array_unique($guards));

        $preferredGuard = $request->session()->get('active_auth_guard');
        $guard = is_string($preferredGuard)
            && in_array($preferredGuard, $guards, true)
            && Auth::guard($preferredGuard)->check()
                ? $preferredGuard
                : collect($guards)->first(
                    fn (string $candidate): bool => Auth::guard($candidate)->check()
                );

        if (! is_string($guard)) {
            return redirect()
                ->route('login')
                ->with('error', 'Please log in again.');
        }

        Auth::shouldUse($guard);
        $authUser = Auth::guard($guard)->user();

        if (! $authUser instanceof User) {
            $this->logoutGuard($request, $guard);

            return redirect()
                ->route('login')
                ->with('error', 'Please log in again.');
        }

        $user = $authUser->loadMissing('role');

        $accountStatusService->deactivateIfDormant($user);

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->logoutGuard($request, $guard);

            return redirect()
                ->route('login')
                ->with('error', 'Your account is not active. Please contact the administrator.');
        }

        if (! $user->email_verified_at) {
            $this->logoutGuard($request, $guard);

            return redirect()
                ->route('login')
                ->with('error', 'Sign in again to verify your email address.');
        }

        if (! $user->role) {
            $this->logoutGuard($request, $guard);

            return redirect()
                ->route('login')
                ->with('error', 'Your account role is not properly configured.');
        }

        $roleSlug = $user->role->slug;
        $expectedGuard = config("auth.role_guards.{$roleSlug}");
        $dashboardRoute = config("auth.role_dashboards.{$roleSlug}");

        if (
            ! is_string($expectedGuard)
            || ! is_string($dashboardRoute)
            || ($guard !== 'web' && ! hash_equals($expectedGuard, $guard))
        ) {
            $this->logoutGuard($request, $guard);

            return redirect()
                ->route('login')
                ->with('error', 'Unauthorized role. Please contact the administrator.');
        }

        $request->session()->put('active_auth_guard', $guard);

        return redirect()->route($dashboardRoute);
    }

    private function logoutGuard(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        if ($request->session()->get('active_auth_guard') === $guard) {
            $request->session()->forget('active_auth_guard');
        }

        // Keep the other role guards in the shared browser session intact.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
    }
}
