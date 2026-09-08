<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserAccountStatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function __construct(
        private readonly UserAccountStatusService $accountStatusService
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            abort(403, 'Unauthorized access.');
        }

        $this->accountStatusService->deactivateIfDormant($user);

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->logoutCurrentGuard($request);
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account is inactive or pending administrator approval.');
        }

        if (! $user->email_verified_at) {
            $this->logoutCurrentGuard($request);
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Sign in again to verify your email address.');
        }

        if (! in_array($user->role->slug, $roles, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        $request->session()->put('active_auth_guard', Auth::getDefaultDriver());

        return $next($request);
    }

    private function logoutCurrentGuard(Request $request): void
    {
        $guard = Auth::getDefaultDriver();

        Auth::guard($guard)->logout();

        if ($request->session()->get('active_auth_guard') === $guard) {
            $request->session()->forget('active_auth_guard');
        }
    }
}
