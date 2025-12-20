<?php

use App\Models\Setting;

if (! function_exists('getSetting')) {
    /**
     * Get a setting value with fallback chain:
     * 1) Provided $settings array/object (if passed)
     * 2) Database setting
     * 3) Environment variable
     * 4) Default value
     *
     * Supports:
     * - getSetting('APP_NAME', 'LorAstro')
     * - getSetting($settings, 'APP_NAME', 'LorAstro')
     */
    function getSetting(array|object|string $settingsOrKey, string|int|float|bool|null $keyOrDefault = null, mixed $default = null): mixed
    {
        // Signature: getSetting('APP_NAME', 'LorAstro')
        if (is_string($settingsOrKey)) {
            $key = $settingsOrKey;
            $defaultValue = $keyOrDefault; // 2nd arg is default in this form

            return Setting::get($key, $defaultValue);
        }

        // Signature: getSetting($settings, 'APP_NAME', 'LorAstro')
        $settings = $settingsOrKey;
        $key = (string) $keyOrDefault;

        // 1) Check provided settings bag first
        if (is_array($settings) && array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        if (is_object($settings) && isset($settings->{$key})) {
            return $settings->{$key};
        }

        // 2) DB -> env -> default handled by your existing model logic
        return Setting::get($key, $default);
    }
}

if (! function_exists('setting')) {
    /**
     * Get a setting value with fallback to environment variable.
     *
     * @param  mixed  $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('setting_set')) {
    /**
     * Set a setting value.
     *
     * @param  mixed  $value
     * @return Setting
     */
    function setting_set(string $key, $value, string $type = 'string', ?string $description = null, bool $isPublic = false)
    {
        return Setting::set($key, $value, $type, $description, $isPublic);
    }
}

if (! function_exists('setting_has')) {
    /**
     * Check if a setting exists in the database.
     */
    function setting_has(string $key): bool
    {
        return Setting::has($key);
    }
}

if (! function_exists('setting_forget')) {
    /**
     * Delete a setting.
     */
    function setting_forget(string $key): bool
    {
        return Setting::forget($key);
    }
}

if (! function_exists('settings_all')) {
    /**
     * Get all settings as an associative array.
     */
    function settings_all(bool $publicOnly = false): array
    {
        return Setting::all($publicOnly);
    }
}
