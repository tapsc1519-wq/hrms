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
        'erp' => [
            'name' => env('ERP_NAME', 'Niyantron ERP'),
            'domain' => env('ERP_DOMAIN', 'erp.niyantron.com'),
            'url' => env('ERP_URL', 'https://erp.niyantron.com'),
            'sso_secret' => env('ERP_SSO_SHARED_SECRET'),
        ],
    ],
];
