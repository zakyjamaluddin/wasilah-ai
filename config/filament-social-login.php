<?php

return [
    'providers' => [
        'google' => env('SOCIAL_LOGIN_GOOGLE_ENABLED', true),
        'facebook' => env('SOCIAL_LOGIN_FACEBOOK_ENABLED', false),
        'github' => env('SOCIAL_LOGIN_GITHUB_ENABLED', false),
        'linkedin' => env('SOCIAL_LOGIN_LINKEDIN_ENABLED', true),
        'microsoft' => env('SOCIAL_LOGIN_MICROSOFT_ENABLED', true),
    ],
];
