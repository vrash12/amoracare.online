<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrevoEmailService
{
    public function sendVerificationCode(
        User $user,
        string $code,
        int $expiresMinutes,
        string $purpose = 'login'
    ): void {
        $driver = (string) config('accounts.email_verification.driver', 'brevo');

        if ($driver === 'log') {
            Log::info('AmoraCare email verification code generated.', [
                'email' => $user->email,
                'code' => $code,
                'expires_minutes' => $expiresMinutes,
                'purpose' => $purpose,
            ]);

            return;
        }

        if ($driver !== 'brevo') {
            throw new RuntimeException("Unsupported email verification driver [{$driver}].");
        }

        $apiKey = trim((string) config('services.brevo.api_key'));
        $senderEmail = trim((string) config('services.brevo.sender_email'));

        if ($apiKey === '' || $senderEmail === '') {
            throw new RuntimeException(
                'Brevo email delivery is not configured. Add BREVO_API_KEY and BREVO_SENDER_EMAIL to the environment.'
            );
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(10)
                ->withHeaders(['api-key' => $apiKey])
                ->post((string) config('services.brevo.api_url'), [
                    'sender' => [
                        'name' => (string) config('services.brevo.sender_name', 'AmoraCare'),
                        'email' => $senderEmail,
                    ],
                    'to' => [[
                        'name' => $user->name,
                        'email' => $user->email,
                        'contactPixelTrackingConsent' => false,
                    ]],
                    'subject' => match ($purpose) {
                        'registration' => 'Verify your AmoraCare sign-up',
                        'email_confirmation' => 'Confirm your AmoraCare email address',
                        default => 'Your AmoraCare login OTP',
                    },
                    'htmlContent' => view('emails.email-verification-code', [
                        'user' => $user,
                        'code' => $code,
                        'expiresMinutes' => $expiresMinutes,
                        'purpose' => $purpose,
                    ])->render(),
                    'tags' => [match ($purpose) {
                        'registration' => 'registration-otp',
                        'email_confirmation' => 'email-confirmation-otp',
                        default => 'login-otp',
                    }],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Brevo verification email connection failed.', [
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('The verification email service is temporarily unavailable.', 0, $exception);
        }

        if ($response->failed()) {
            Log::warning('Brevo verification email request failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('The verification email could not be sent. Please try again later.');
        }
    }
}
