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

        $purpose = $this->verificationPurpose($request);

        return view('auth.verify-email', [
            'maskedEmail' => $this->maskEmail($user->email),
            'expiresMinutes' => $emailVerificationService->expiresMinutes(),
            'purpose' => $purpose,
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

        $purpose = $this->verificationPurpose($request);

        if (! $user->role || ($purpose === 'login' && $user->status !== User::STATUS_ACTIVE)) {
            $this->clearVerificationSession($request);

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

        if ($purpose === 'registration') {
            $this->clearVerificationSession($request);
            $request->session()->regenerate();

            return redirect()
                ->route('parent.application.submitted')
                ->with('application_email', $user->email);
        }

        $remember = (bool) $request->session()->get('email_verification_remember', false);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->clearVerificationSession($request);

        $user->update(['last_login_at' => now()]);

        return redirect()
            ->intended(route('dashboard'))
            ->with('success', 'Your login OTP was verified successfully.');
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
            $sent = $emailVerificationService->sendCode(
                $user,
                $this->verificationPurpose($request)
            );
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

    private function verificationPurpose(Request $request): string
    {
        return $request->session()->get('email_verification_purpose') === 'registration'
            ? 'registration'
            : 'login';
    }

    private function clearVerificationSession(Request $request): void
    {
        $request->session()->forget([
            'email_verification_user_id',
            'email_verification_remember',
            'email_verification_purpose',
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($localPart, 0, min(2, mb_strlen($localPart)));
        $hiddenLength = max(2, mb_strlen($localPart) - mb_strlen($visible));

        return $visible.str_repeat('•', $hiddenLength).'@'.$domain;
    }
}
