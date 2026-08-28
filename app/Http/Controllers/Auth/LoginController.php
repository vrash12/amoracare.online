<?php

// laravel-app/app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
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
        UserAccountStatusService $accountStatusService,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
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

        if ($user->status !== User::STATUS_ACTIVE) {
            $message = $user->status === User::STATUS_PENDING
                ? 'Your account is pending administrator approval.'
                : 'Your account is inactive. Please contact the administrator.';

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        if (! $user->role) {
            throw ValidationException::withMessages([
                'email' => 'Your account role is not configured. Please contact the administrator.',
            ]);
        }

        if (! $user->email_verified_at) {
            $request->session()->put([
                'email_verification_user_id' => $user->id,
                'email_verification_remember' => $request->boolean('remember'),
            ]);

            try {
                $sent = $emailVerificationService->sendCode($user);
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'email' => $exception->getMessage(),
                ]);
            }

            return redirect()
                ->route('email.verification.notice')
                ->with(
                    'success',
                    $sent
                        ? 'A verification code was sent to your email address.'
                        : 'A verification code was sent recently. Check your inbox or wait before requesting another.'
                );
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
