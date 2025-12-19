<?php

// config/session.php
// Make sure these settings are correct for cross-origin sessions

use Illuminate\Support\Str;

return [
    // ... other settings ...
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'LarAstro'), '_').'_session'
    ),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null), // Should be null for localhost
    'secure' => env('SESSION_SECURE_COOKIE', false), // false for local dev
    'http_only' => true,
    'same_site' => 'lax', // Important: use 'lax' for cross-origin
];