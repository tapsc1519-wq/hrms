<?php

return [
    'main_domain' => env('NIYANTRON_DOMAIN', 'niyantron.com'),

    'partner_domain' => env('PARTNER_DOMAIN', 'partner.niyantron.com'),

    'platform_domain' => env('PLATFORM_DOMAIN', 'platform.niyantron.com'),

    'default_product' => env('DEFAULT_PRODUCT', 'opsbridge'),

    'products' => [
        'opsbridge' => [
            'name' => env('OPSBRIDGE_NAME', 'OpsBridge'),
            'domain' => env('OPSBRIDGE_DOMAIN', 'opsbridge.niyantron.com'),
            'login_title' => env('OPSBRIDGE_LOGIN_TITLE', 'Welcome back to OpsBridge'),
            'login_subtitle' => env('OPSBRIDGE_LOGIN_SUBTITLE', 'Access your IT operations workspace.'),
        ],
    ],
];
