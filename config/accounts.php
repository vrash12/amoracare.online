<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatic account inactivity
    |--------------------------------------------------------------------------
    |
    | An active account is changed to inactive when it has no successful login
    | or activation within this number of days. Pending accounts are excluded.
    |
    */
    'inactivity_days' => (int) env('ACCOUNT_INACTIVITY_DAYS', 60),

    'email_verification' => [
        'driver' => env('EMAIL_VERIFICATION_DRIVER', 'brevo'),
        'expires_minutes' => (int) env('EMAIL_VERIFICATION_EXPIRES_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('EMAIL_VERIFICATION_RESEND_SECONDS', 60),
        'max_attempts' => (int) env('EMAIL_VERIFICATION_MAX_ATTEMPTS', 5),
    ],
];
