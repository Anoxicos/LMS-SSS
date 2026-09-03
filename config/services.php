<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Over
    |--------------------------------------------------------------------------
    */

    'voice_over' => [
        'provider' => env(
            'VOICE_OVER_PROVIDER',
            'free_tts'
        ),

        'default_lang' => env(
            'VOICE_OVER_DEFAULT_LANG',
            'en-us'
        ),

        'default_voice' => env(
            'VOICE_OVER_DEFAULT_VOICE',
            'John'
        ),

        'free_tts' => [
            'url' => env(
                'FREE_TTS_API_URL',
                'https://api.voicerss.org/'
            ),

            'key' => env(
                'FREE_TTS_API_KEY'
            ),

            'verify_ssl' => env(
                'FREE_TTS_VERIFY_SSL',
                true
            ),
        ],

        'openai' => [
            'key' => env(
                'OPENAI_API_KEY'
            ),
        ],

        'elevenlabs' => [
            'key' => env(
                'ELEVENLABS_API_KEY'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Microsoft 365 Mail OAuth2
    |--------------------------------------------------------------------------
    |
    | IMPORTANT :
    | microsoft_mail doit être ICI,
    | et PAS à l'intérieur de voice_over.
    |
    */

    'microsoft_mail' => [
        'tenant_id' => env(
            'MICROSOFT_TENANT_ID'
        ),

        'client_id' => env(
            'MICROSOFT_CLIENT_ID'
        ),

        'client_secret' => env(
            'MICROSOFT_CLIENT_SECRET'
        ),

        'username' => env(
            'MICROSOFT_MAIL_USERNAME'
        ),

        'from' => env(
            'MICROSOFT_MAIL_FROM'
        ),

        'from_name' => env(
            'MICROSOFT_MAIL_FROM_NAME',
            'SafeSky LMS'
        ),
    ],

    'company_requests' => [
        'parent_email' => env(
            'COMPANY_REQUEST_PARENT_EMAIL'
        ),
    ],

];