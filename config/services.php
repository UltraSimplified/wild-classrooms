<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    |
    | A Maps browser key is necessarily visible in page source, so restrict it
    | by HTTP referrer in the Google Cloud Console. The old site pasted this
    | key directly into three Blade templates.
    |
    */

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Notifications
    |--------------------------------------------------------------------------
    |
    | Who hears about new registrations and new spottings. These addresses were
    | hardcoded in two controllers, and had drifted apart: one said
    | stroudvalleysproject.org and the other stroudvalleysproject.org.uk, so one
    | set of those notifications was going nowhere.
    |
    */

    'staff_notifications' => array_filter(array_map('trim', explode(',', (string) env('STAFF_NOTIFICATION_EMAILS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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

];
