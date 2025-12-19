<?php

declare(strict_types=1);

use App\Http\Middleware\OrchidAdminAccess;

return array_replace_recursive(
    require base_path('vendor/orchid/platform/config/platform.php'),
    [
        'domain' => env('ORCHID_DOMAIN', null),
        'prefix' => env('ORCHID_PREFIX', 'admin'),

        // keep your desired middleware for platform routes
        'middleware' => [
            'web',
            'platform',
            // OrchidAdminAccess::class,
        ],

        'index' => 'platform.dashboard',

        'template' => [
            'header' => env('APP_NAME', 'Laravel') . ' Admin',
            'footer' => 'Powered by Orchid',
        ],
    ]
);
