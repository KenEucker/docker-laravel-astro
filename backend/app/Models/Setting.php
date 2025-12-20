<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Setting extends Model
{
    use AsSource, Filterable;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value with fallback to environment variable.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if neither database nor env has the setting
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Try to get from cache first
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if ($setting) {
                return static::castValue($setting->value, $setting->type);
            }

            // Fallback to environment variable
            $envValue = env(strtoupper($key));

            if ($envValue !== null) {
                return $envValue;
            }

            return $default;
        });
    }

    /**
     * Set a setting value.
     *
     * @param  mixed  $value
     * @return static
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null, bool $isPublic = false)
    {
        $stringValue = static::valueToString($value, $type);

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        // Clear cache
        Cache::forget("setting.{$key}");

        return $setting;
    }

    /**
     * Check if a setting exists in the database.
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Delete a setting.
     */
    public static function forget(string $key): bool
    {
        Cache::forget("setting.{$key}");

        return static::where('key', $key)->delete() > 0;
    }

    /**
     * Get all settings as an associative array.
     *
     * @param  bool  $publicOnly  Only return public settings
     */
    public static function all($publicOnly = false): array
    {
        $query = static::query();

        if ($publicOnly) {
            $query->where('is_public', true);
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => static::castValue($setting->value, $setting->type)];
        })->toArray();
    }

    /**
     * Cast a string value to the appropriate type.
     *
     * @param  string|null  $value
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'array', 'json' => json_decode($value, true),
            'object' => json_decode($value),
            default => $value,
        };
    }

    /**
     * Convert a value to a string for storage.
     *
     * @param  mixed  $value
     */
    protected static function valueToString($value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'array', 'json', 'object' => json_encode($value),
            default => (string) $value,
        };
    }
}
