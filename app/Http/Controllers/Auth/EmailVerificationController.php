<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class EmailVerificationController extends Controller
{
    public function show(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): View|RedirectResponse {
        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Sign in again to request an email verification code.');
        }

        if ($user->email_verified_at) {
            return redirect()
                ->route('login')
                ->with('success', 'Your email address is already verified. You may now sign in.');
        }

        return view('auth.verify-email', [
            'maskedEmail' => $this->maskEmail($user->email),
            'expiresMinutes' => $emailVerificationService->expiresMinutes(),
        ]);
    }

    public function verify(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Your verification session expired. Sign in again.');
        }

        if ($user->status !== User::STATUS_ACTIVE || ! $user->role) {
            $request->session()->forget([
                'email_verification_user_id',
                'email_verification_remember',
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'Your account is not available for verification. Contact the administrator.');
        }

        $result = $emailVerificationService->verifyCode($user, $validated['code']);

        if (! $result['verified']) {
            throw ValidationException::withMessages([
                'code' => $result['message'],
            ]);
        }

        $remember = (bool) $request->session()->get('email_verification_remember', false);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget([
            'email_verification_user_id',
            'email_verification_remember',
        ]);

        $user->update(['last_login_at' => now()]);

        return redirect()
            ->intended(route('dashboard'))
            ->with('success', 'Your email address was verified successfully.');
    }

    public function resend(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Your verification session expired. Sign in again.');
        }

        try {
            $sent = $emailVerificationService->sendCode($user);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with(
            'success',
            $sent
                ? 'A new verification code was sent.'
                : 'A code was sent recently. Please wait before requesting another.'
        );
    }

    private function verificationUser(Request $request): ?User
    {
        $userId = $request->session()->get('email_verification_user_id');

        return $userId ? User::with('role')->find($userId) : null;
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($localPart, 0, min(2, mb_strlen($localPart)));
        $hiddenLength = max(2, mb_strlen($localPart) - mb_strlen($visible));

        return $visible.str_repeat('•', $hiddenLength).'@'.$domain;
    }
}
