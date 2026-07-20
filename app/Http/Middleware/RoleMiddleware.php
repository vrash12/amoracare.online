<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user || !$user->role) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->status !== 'active') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with('error', 'Your account is not active.');
        }

        if (!in_array($user->role->slug, $roles, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}