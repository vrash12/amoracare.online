<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
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

    public function sendCode(User $user, string $purpose = 'login'): bool
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
            $this->emailService->sendVerificationCode(
                $user,
                $code,
                $this->expiresMinutes(),
                $purpose
            );
        } catch (\Throwable $exception) {
            $verificationCode->delete();

            throw $exception;
        }

        return true;
    }

    /**
     * Send an OTP before a public applicant account exists.
     *
     * @return array{code_hash: string, attempts: int, sent_at: string, expires_at: string}
     */
    public function createPendingChallenge(
        string $name,
        string $email,
        string $purpose = 'registration'
    ): array {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sentAt = now();

        $recipient = new User([
            'name' => $name,
            'email' => strtolower(trim($email)),
        ]);

        $this->emailService->sendVerificationCode(
            $recipient,
            $code,
            $this->expiresMinutes(),
            $purpose
        );

        return [
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => $sentAt->toIso8601String(),
            'expires_at' => $sentAt->copy()->addMinutes($this->expiresMinutes())->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $challenge
     * @return array{sent: bool, challenge: array<string, mixed>}
     */
    public function resendPendingChallenge(
        array $challenge,
        string $name,
        string $email,
        string $purpose = 'registration'
    ): array {
        $sentAt = $this->challengeDate($challenge['sent_at'] ?? null);
        $expiresAt = $this->challengeDate($challenge['expires_at'] ?? null);

        if (
            is_string($challenge['code_hash'] ?? null)
            && $sentAt?->greaterThan(now()->subSeconds($this->resendCooldownSeconds()))
            && $expiresAt?->isFuture()
        ) {
            return ['sent' => false, 'challenge' => $challenge];
        }

        return [
            'sent' => true,
            'challenge' => $this->createPendingChallenge($name, $email, $purpose),
        ];
    }

    /**
     * @param  array<string, mixed>  $challenge
     * @return array{verified: bool, message: string, challenge: array<string, mixed>|null}
     */
    public function verifyPendingChallenge(array $challenge, string $code): array
    {
        $codeHash = $challenge['code_hash'] ?? null;
        $expiresAt = $this->challengeDate($challenge['expires_at'] ?? null);
        $attempts = max(0, (int) ($challenge['attempts'] ?? 0));

        if (! is_string($codeHash) || $codeHash === '' || ! $expiresAt) {
            return [
                'verified' => false,
                'message' => 'No verification code was found. Request a new code.',
                'challenge' => null,
            ];
        }

        if ($expiresAt->lessThanOrEqualTo(now())) {
            return [
                'verified' => false,
                'message' => 'The verification code has expired. Request a new code.',
                'challenge' => null,
            ];
        }

        if ($attempts >= $this->maxAttempts()) {
            return [
                'verified' => false,
                'message' => 'Too many incorrect attempts. Request a new code.',
                'challenge' => null,
            ];
        }

        if (! Hash::check($code, $codeHash)) {
            $attempts++;

            if ($attempts >= $this->maxAttempts()) {
                return [
                    'verified' => false,
                    'message' => 'Too many incorrect attempts. Request a new code.',
                    'challenge' => null,
                ];
            }

            $challenge['attempts'] = $attempts;

            return [
                'verified' => false,
                'message' => 'The verification code is incorrect.',
                'challenge' => $challenge,
            ];
        }

        return [
            'verified' => true,
            'message' => 'Your email address has been verified.',
            'challenge' => null,
        ];
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

    private function challengeDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
