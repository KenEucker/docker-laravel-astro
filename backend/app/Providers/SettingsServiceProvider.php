<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, fn () => new SettingsService);
    }

    public function boot(): void
    {
        try {
            /** @var \App\Services\SettingsService $settings */
            $settings = $this->app->make(\App\Services\SettingsService::class);
            $app_name = $settings->get('APP_NAME', env('APP_NAME', 'LarAstro'));
            logger()->info('SettingsServiceProvider boot APP_NAME', [
                'db_or_env' => $settings->get('APP_NAME', 'LarAstro'),
                'config_app_name_before' => config('app.name'),
            ]);

            // IMPORTANT: drive the app name used by your Blade header
            \Illuminate\Support\Facades\Config::set(
                'app.name',
                $app_name
            );

            // Orchid branding template (view name)
            \Illuminate\Support\Facades\Config::set('platform.template.header', 'brand.header');

            $domain = $settings->get('ORCHID_DOMAIN', null);
            if ($domain !== null) {
                \Illuminate\Support\Facades\Config::set('platform.domain', $domain);
            }

            \Illuminate\Support\Facades\Config::set('platform.prefix', $settings->get('ORCHID_PREFIX', 'admin'));
        } catch (\Throwable $e) {
            // fallback so artisan never dies
            \Illuminate\Support\Facades\Config::set('app.name', env('APP_NAME', 'LarAstro'));
            \Illuminate\Support\Facades\Config::set('platform.template.header', 'brand.header');
        }
    }
}
