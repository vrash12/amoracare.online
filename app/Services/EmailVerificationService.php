<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmailVerificationService
{
    public function __construct(
        private readonly BrevoEmailService $emailService
    ) {}

    public function expiresMinutes(): int
    {
        return max(1, (int) config('accounts.email_verification.expires_minutes', 10));
    }

    public function resendCooldownSeconds(): int
    {
        return max(1, (int) config('accounts.email_verification.resend_cooldown_seconds', 60));
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('accounts.email_verification.max_attempts', 5));
    }

    public function sendCode(User $user): bool
    {
        $existing = $user->emailVerificationCode()->first();

        if (
            $existing
            && $existing->expires_at->isFuture()
            && $existing->sent_at->greaterThan(now()->subSeconds($this->resendCooldownSeconds()))
        ) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verificationCode = $user->emailVerificationCode()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'sent_at' => now(),
                'expires_at' => now()->addMinutes($this->expiresMinutes()),
            ]
        );

        try {
            $this->emailService->sendVerificationCode($user, $code, $this->expiresMinutes());
        } catch (\Throwable $exception) {
            $verificationCode->delete();

            throw $exception;
        }

        return true;
    }

    /**
     * @return array{verified: bool, message: string}
     */
    public function verifyCode(User $user, string $code): array
    {
        $verificationCode = $user->emailVerificationCode()->first();

        if (! $verificationCode) {
            return [
                'verified' => false,
                'message' => 'No verification code was found. Request a new code.',
            ];
        }

        if ($verificationCode->expires_at->lessThanOrEqualTo(now())) {
            $verificationCode->delete();

            return [
                'verified' => false,
                'message' => 'The verification code has expired. Request a new code.',
            ];
        }

        if ($verificationCode->attempts >= $this->maxAttempts()) {
            $verificationCode->delete();

            return [
                'verified' => false,
                'message' => 'Too many incorrect attempts. Request a new code.',
            ];
        }

        if (! Hash::check($code, $verificationCode->code_hash)) {
            $verificationCode->increment('attempts');

            if ($verificationCode->fresh()->attempts >= $this->maxAttempts()) {
                $verificationCode->delete();

                return [
                    'verified' => false,
                    'message' => 'Too many incorrect attempts. Request a new code.',
                ];
            }

            return [
                'verified' => false,
                'message' => 'The verification code is incorrect.',
            ];
        }

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $verificationCode->delete();

        return [
            'verified' => true,
            'message' => 'Your email address has been verified.',
        ];
    }
}
