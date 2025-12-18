<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get a setting value with fallback to environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setting_set')) {
    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @param bool $isPublic
     * @return Setting
     */
    function setting_set(string $key, $value, string $type = 'string', ?string $description = null, bool $isPublic = false)
    {
        return Setting::set($key, $value, $type, $description, $isPublic);
    }
}

if (!function_exists('setting_has')) {
    /**
     * Check if a setting exists in the database.
     *
     * @param string $key
     * @return bool
     */
    function setting_has(string $key): bool
    {
        return Setting::has($key);
    }
}

if (!function_exists('setting_forget')) {
    /**
     * Delete a setting.
     *
     * @param string $key
     * @return bool
     */
    function setting_forget(string $key): bool
    {
        return Setting::forget($key);
    }
}

if (!function_exists('settings_all')) {
    /**
     * Get all settings as an associative array.
     *
     * @param bool $publicOnly
     * @return array
     */
    function settings_all(bool $publicOnly = false): array
    {
        return Setting::all($publicOnly);
    }
}
