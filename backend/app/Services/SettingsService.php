<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingsService
{
    /**
     * Cache keys (separate namespaces)
     */
    private const CACHE_KEY_ALL = 'settings:all';

    private const CACHE_KEY_PUBLIC = 'settings:public';

    /**
     * Fetch a setting using fallback chain:
     * 1) Provided $settings array/object (if passed)
     * 2) Database (cached)
     * 3) Environment variable
     * 4) Default
     */
    public function get(string $key, mixed $default = null, array|object|null $settings = null): mixed
    {
        // 1) Caller-provided settings bag (fast path)
        if (is_array($settings) && array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        if (is_object($settings) && isset($settings->{$key})) {
            return $settings->{$key};
        }

        // 2) DB (cached)
        $all = $this->all(false);
        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        // 3) env
        $envValue = getenv($key);
        if ($envValue !== null) {
            return $envValue;
        }

        // 4) default
        return $default;
    }

    /**
     * Return all settings as [key => value], cached.
     * If $publicOnly = true, returns only public settings.
     */
    public function all(bool $publicOnly = false): array
    {
        // IMPORTANT: never cache a "DB not ready" result
        if (! $this->settingsTableReady()) {
            return [];
        }

        $cacheKey = $publicOnly ? self::CACHE_KEY_PUBLIC : self::CACHE_KEY_ALL;

        // Use a short TTL so development changes don’t get “stuck”
        // You can increase this later (or go back to forever once invalidation is solid).
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($publicOnly) {
            $query = Setting::query()->select(['key', 'value']);

            if ($publicOnly && Schema::hasColumn('settings', 'is_public')) {
                $query->where('is_public', true);
            }

            return $query->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear cached settings.
     */
    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        Cache::forget(self::CACHE_KEY_PUBLIC);
    }

    /**
     * Convenience wrappers that also clear cache.
     * (Use these from helpers / controllers to keep cache coherent.)
     */
    public function set(string $key, mixed $value, string $type = 'string', ?string $description = null, bool $isPublic = false): Setting
    {
        $setting = Setting::set($key, $value, $type, $description, $isPublic);
        $this->forgetCache();

        return $setting;
    }

    public function forget(string $key): bool
    {
        $result = Setting::forget($key);
        $this->forgetCache();

        return $result;
    }

    private function settingsTableReady(): bool
    {
        try {
            // Schema call can fail if DB is down; swallow and behave as "no settings"
            return Schema::hasTable('settings') && Schema::hasColumn('settings', 'key');
        } catch (Throwable) {
            return false;
        }
    }
}
