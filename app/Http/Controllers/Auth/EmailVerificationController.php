<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentMatchingProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class EmailVerificationController extends Controller
{
    public function show(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): View|RedirectResponse {
        $purpose = $this->verificationPurpose($request);

        if ($purpose === 'registration') {
            $pendingRegistration = $this->pendingRegistration($request);

            if (! $pendingRegistration) {
                return redirect()
                    ->route('parent.application.create')
                    ->with('error', 'Your sign-up verification session expired. Please submit the application again.');
            }

            return view('auth.verify-email', [
                'maskedEmail' => $this->maskEmail($pendingRegistration['data']['email']),
                'expiresMinutes' => $emailVerificationService->expiresMinutes(),
                'purpose' => $purpose,
            ]);
        }

        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Sign in again to request an email verification code.');
        }

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

        $purpose = $this->verificationPurpose($request);

        if ($purpose === 'registration') {
            return $this->verifyRegistration(
                $request,
                $emailVerificationService,
                $validated['code']
            );
        }

        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Your verification session expired. Sign in again.');
        }

        $guard = $this->verificationGuard($request, $user);

        if (
            ! $user->role
            || ! $guard
            || ($purpose === 'login' && $user->status !== User::STATUS_ACTIVE)
            || ($purpose === 'email_confirmation'
                && ! in_array($user->status, [User::STATUS_ACTIVE, User::STATUS_PENDING], true))
        ) {
            $this->clearVerificationSession($request);

            return redirect()
                ->route('login')
                ->with('error', 'Your account is not available for verification. Contact the administrator.');
        }

        $challenge = $request->session()->get('email_verification_challenge', []);
        $result = $emailVerificationService->verifyPendingChallenge(
            is_array($challenge) ? $challenge : [],
            $validated['code']
        );

        if (is_array($result['challenge'])) {
            $request->session()->put('email_verification_challenge', $result['challenge']);
        } else {
            $request->session()->forget('email_verification_challenge');
        }

        if (! $result['verified']) {
            throw ValidationException::withMessages([
                'code' => $result['message'],
            ]);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        }

        if ($purpose === 'email_confirmation' && $user->status === User::STATUS_PENDING) {
            $this->clearVerificationSession($request);
            $request->session()->regenerate();

            return redirect()
                ->route('login')
                ->with('success', 'Your email address has been verified. Your account is pending administrator approval.');
        }

        $remember = (bool) $request->session()->get('email_verification_remember', false);

        Auth::guard($guard)->login($user, $remember);
        Auth::shouldUse($guard);
        $request->session()->regenerate();
        $this->clearVerificationSession($request);
        $request->session()->put('active_auth_guard', $guard);
        $request->session()->put('account_password_hash.'.$guard.'.'.$user->getKey(), $user->password);

        $user->update(['last_login_at' => now()]);

        $dashboardRoute = config("auth.role_dashboards.{$user->role->slug}");

        if (! is_string($dashboardRoute) || $dashboardRoute === '') {
            Auth::guard($guard)->logout();

            return redirect()
                ->route('login')
                ->with('error', 'Your account dashboard is not configured. Please contact the administrator.');
        }

        return redirect()
            ->route($dashboardRoute)
            ->with(
                'success',
                $purpose === 'email_confirmation'
                    ? 'Your email address was verified and you are now logged in.'
                    : 'Your login OTP was verified successfully.'
            );
    }

    public function resend(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
        if ($this->verificationPurpose($request) === 'registration') {
            return $this->resendRegistrationCode($request, $emailVerificationService);
        }

        $user = $this->verificationUser($request);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Your verification session expired. Sign in again.');
        }

        try {
            $challenge = $request->session()->get('email_verification_challenge', []);
            $result = $emailVerificationService->resendPendingChallenge(
                is_array($challenge) ? $challenge : [],
                $user->name,
                $user->email,
                $this->verificationPurpose($request)
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->put('email_verification_challenge', $result['challenge']);

        return back()->with(
            'success',
            $result['sent']
                ? 'A new verification code was sent.'
                : 'A code was sent recently. Please wait before requesting another.'
        );
    }

    private function verificationUser(Request $request): ?User
    {
        $userId = $request->session()->get('email_verification_user_id');

        return $userId ? User::with('role')->find($userId) : null;
    }

    /**
     * @return array{data: array<string, mixed>, challenge: array<string, mixed>}|null
     */
    private function pendingRegistration(Request $request): ?array
    {
        $pending = $request->session()->get('pending_parent_registration');

        if (! is_array($pending) || ! is_array($pending['data'] ?? null)) {
            return null;
        }

        $data = $pending['data'];

        if (
            ! is_string($data['name'] ?? null)
            || ! is_string($data['email'] ?? null)
            || ! is_string($data['password_hash'] ?? null)
        ) {
            return null;
        }

        return [
            'data' => $data,
            'challenge' => is_array($pending['challenge'] ?? null)
                ? $pending['challenge']
                : [],
        ];
    }

    private function verifyRegistration(
        Request $request,
        EmailVerificationService $emailVerificationService,
        string $code
    ): RedirectResponse {
        $pending = $this->pendingRegistration($request);

        if (! $pending) {
            return redirect()
                ->route('parent.application.create')
                ->with('error', 'Your sign-up verification session expired. Please submit the application again.');
        }

        $result = $emailVerificationService->verifyPendingChallenge(
            $pending['challenge'],
            $code
        );

        $pending['challenge'] = $result['challenge'] ?? [];
        $request->session()->put('pending_parent_registration', $pending);

        if (! $result['verified']) {
            throw ValidationException::withMessages([
                'code' => $result['message'],
            ]);
        }

        $data = $pending['data'];
        $acceptedTermsVersion = (string) ($data['terms_version'] ?? '');
        $currentTermsVersion = (string) config('legal.terms_version');

        if (
            $acceptedTermsVersion === ''
            || ! hash_equals($currentTermsVersion, $acceptedTermsVersion)
        ) {
            $this->clearVerificationSession($request);

            return redirect()
                ->route('parent.application.create')
                ->with('error', 'The Terms and Conditions were updated. Please review them and submit the application again.');
        }

        $email = strtolower(trim((string) $data['email']));

        if (User::query()->where('email', $email)->exists()) {
            $this->clearVerificationSession($request);

            return redirect()
                ->route('parent.application.create')
                ->withErrors(['email' => 'An account with this email address already exists.']);
        }

        try {
            $parent = DB::transaction(function () use ($data, $email): User {
                $role = Role::firstOrCreate(
                    ['slug' => 'prospective_parent'],
                    [
                        'name' => 'Prospective Adoptive Parent',
                        'description' => 'Applicant for adoption assistance, case monitoring, and matching review.',
                    ]
                );

                $parent = User::create([
                    'role_id' => $role->id,
                    'name' => (string) $data['name'],
                    'email' => $email,
                    'phone_number' => (string) ($data['phone_number'] ?? ''),
                    'status' => User::STATUS_PENDING,
                    'password' => (string) $data['password_hash'],
                ]);

                $verificationFields = ['email_verified_at' => now()];
                if (Schema::hasColumn('users', 'terms_accepted_version')) {
                    $verificationFields['terms_accepted_version'] = (string) $data['terms_version'];
                    $verificationFields['terms_accepted_at'] = $data['consented_at'];
                    $verificationFields['must_change_password'] = false;
                }
                $parent->forceFill($verificationFields)->saveQuietly();

                ParentMatchingProfile::create([
                    'user_id' => $parent->id,
                    'preferred_child_sex' => (string) ($data['preferred_child_sex'] ?? 'any'),
                    'min_child_age' => (int) ($data['min_child_age'] ?? 0),
                    'max_child_age' => (int) ($data['max_child_age'] ?? 18),
                    'open_to_special_needs' => (bool) ($data['open_to_special_needs'] ?? false),
                    'home_study_verified' => false,
                    'financial_capacity_score' => 0,
                    'housing_score' => 0,
                    'parenting_capacity_score' => 0,
                    'matching_notes' => null,
                ]);

                return $parent;
            });
        } catch (QueryException $exception) {
            if (User::query()->where('email', $email)->exists()) {
                $this->clearVerificationSession($request);

                return redirect()
                    ->route('parent.application.create')
                    ->withErrors(['email' => 'An account with this email address already exists.']);
            }

            throw $exception;
        }

        $this->clearVerificationSession($request);
        $request->session()->regenerate();

        return redirect()
            ->route('parent.application.submitted')
            ->with('application_email', $parent->email);
    }

    private function resendRegistrationCode(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): RedirectResponse {
        $pending = $this->pendingRegistration($request);

        if (! $pending) {
            return redirect()
                ->route('parent.application.create')
                ->with('error', 'Your sign-up verification session expired. Please submit the application again.');
        }

        try {
            $result = $emailVerificationService->resendPendingChallenge(
                $pending['challenge'],
                (string) $pending['data']['name'],
                (string) $pending['data']['email'],
                'registration'
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $pending['challenge'] = $result['challenge'];
        $request->session()->put('pending_parent_registration', $pending);

        return back()->with(
            'success',
            $result['sent']
                ? 'A new verification code was sent.'
                : 'A code was sent recently. Please wait before requesting another.'
        );
    }

    private function verificationPurpose(Request $request): string
    {
        $purpose = $request->session()->get('email_verification_purpose');

        return in_array($purpose, ['registration', 'email_confirmation'], true)
            ? $purpose
            : 'login';
    }

    private function verificationGuard(Request $request, User $user): ?string
    {
        $roleSlug = $user->role?->slug;
        $expectedGuard = $roleSlug ? config("auth.role_guards.{$roleSlug}") : null;
        $sessionGuard = $request->session()->get('email_verification_guard');

        if (! is_string($expectedGuard) || $expectedGuard === '') {
            return null;
        }

        return is_string($sessionGuard) && hash_equals($expectedGuard, $sessionGuard)
            ? $expectedGuard
            : null;
    }

    private function clearVerificationSession(Request $request): void
    {
        $request->session()->forget([
            'email_verification_user_id',
            'email_verification_remember',
            'email_verification_purpose',
            'email_verification_guard',
            'email_verification_challenge',
            'pending_parent_registration',
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
