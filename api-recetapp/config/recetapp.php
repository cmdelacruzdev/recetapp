<?php

return [
    'frontend_url' => env('FRONTEND_URL'),
    'superadmin_email' => env('SUPERADMIN_EMAIL'),
    'invitation_token_hours' => 48,
    'limits' => [
        'ingredients' => 750,
        'recipes' => 250,
        'shopping' => 250,
        'users_per_house' => 5,
        'total_users' => 100,
    ],
];
