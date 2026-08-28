<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'legal_guidance' => [
        'url' => env(
            'LEGAL_GUIDANCE_API_URL',
            'http://127.0.0.1:8001/api/legal-guidance/ask/'
        ),

        'api_key' => env('LEGAL_GUIDANCE_INTERNAL_API_KEY'),
    ],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'matching_model' => env('OPENAI_MATCHING_MODEL', 'gpt-4.1-mini'),
    ],

    'brevo' => [
        'api_url' => env('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email'),
        'api_key' => env('BREVO_API_KEY'),
        'sender_email' => env('BREVO_SENDER_EMAIL'),
        'sender_name' => env('BREVO_SENDER_NAME', env('APP_NAME', 'AmoraCare')),
    ],

];
