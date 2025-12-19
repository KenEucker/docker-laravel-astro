<?php

declare(strict_types=1);

use App\Http\Middleware\OrchidAdminAccess;

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where the Orchid admin panel will be available.
    | Set to null to use the main application domain.
    |
    */

    'domain' => env('ORCHID_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix method will be used for the Orchid admin panel.
    |
    */

    'prefix' => env('ORCHID_PREFIX', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | This middleware will be assigned to every Orchid route, giving you the
    | chance to add your own middleware to this stack or override any of
    | the existing middleware. Or, you can stick with this stack.
    |
    */

    'middleware' => [
        'web',
        OrchidAdminAccess::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Main Route
    |--------------------------------------------------------------------------
    |
    | The main route is used to determine which screen will be displayed
    | when the user navigates to the dashboard.
    |
    */

    'index' => 'platform.dashboard',

    /*
    |--------------------------------------------------------------------------
    | Dashboard Resource
    |--------------------------------------------------------------------------
    |
    | Automatically connect the stored links.
    |
    */

    'resource' => [
        'stylesheets' => [],
        'scripts'     => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template
    |--------------------------------------------------------------------------
    |
    | The template used for rendering the dashboard.
    |
    */

    'template' => [
        'header' => env('APP_NAME', 'Laravel') . ' Admin',
        'footer' => 'Powered by Orchid',
    ],

];
