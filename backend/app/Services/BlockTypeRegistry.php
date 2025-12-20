<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Block Type Registry Service
 *
 * Centralizes block type definitions, validation, and enforcement.
 * Ensures only known block types are created and their data is valid.
 */
class BlockTypeRegistry
{
    /**
     * Get all registered block types.
     */
    public static function all(): array
    {
        return config('blocks.types', []);
    }

    /**
     * Get a specific block type definition.
     */
    public static function get(string $type): ?array
    {
        return config("blocks.types.{$type}");
    }

    /**
     * Check if a block type is registered.
     */
    public static function exists(string|null $type): bool
    {
        return $type !== null && self::get($type) !== null;
    }

    /**
     * Check if a block type is restricted (requires special permission).
     */
    public static function isRestricted(string $type): bool
    {
        $definition = self::get($type);

        return $definition['restricted'] ?? false;
    }

    /**
     * Get validation rules for a block type's data field.
     */
    public static function getValidationRules(string $type): array
    {
        $definition = self::get($type);

        return $definition['validation_rules'] ?? [];
    }

    /**
     * Validate block data for a given type.
     *
     * @throws ValidationException if validation fails
     */
    public static function validateData(string $type, array $data): array
    {
        if (! self::exists($type)) {
            throw ValidationException::withMessages([
                'type' => ["Unknown block type: {$type}"],
            ]);
        }

        $rules = self::getValidationRules($type);

        // Prepend 'data.' to all rules for nested validation
        $nestedRules = [];
        foreach ($rules as $key => $rule) {
            $nestedRules["data.{$key}"] = $rule;
        }

        $validator = Validator::make(['data' => $data], $nestedRules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated()['data'];
    }

    /**
     * Get a list of all block type keys.
     */
    public static function getTypeKeys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Get a list of non-restricted block types.
     */
    public static function getNonRestrictedTypes(): array
    {
        return array_filter(self::all(), function ($definition) {
            return ! ($definition['restricted'] ?? false);
        });
    }

    /**
     * Get block types formatted for select dropdown.
     */
    public static function getTypesForSelect(): array
    {
        $types = self::all();

        $options = [];

        foreach ($types as $key => $definition) {
            // definition can be array|string|object — normalize to a string label
            if (is_array($definition)) {
                $label = $definition['label'] ?? $definition['name'] ?? $key;
                $restricted = (bool) ($definition['restricted'] ?? false);
            } elseif (is_object($definition)) {
                $label = $definition->label ?? $definition->name ?? $key;
                $restricted = (bool) ($definition->restricted ?? false);
            } else {
                // if config uses 'hero' => 'Hero'
                $label = $definition;
                $restricted = false;
            }

            // If label is still not scalar, fall back to the key
            if (! is_scalar($label)) {
                $label = $key;
            }

            $label = (string) $label;

            if ($restricted) {
                $label .= ' (Restricted)';
            }

            $options[$key] = $label;
        }

        return $options;
    }

    /**
     * Sanitize block data based on type.
     * Apply type-specific sanitation strategies.
     */
    public static function sanitize(string $type, array $data): array
    {
        // For richText: ensure markdown is safe
        if ($type === 'richText' && isset($data['markdown'])) {
            // Markdown itself is safe; we'll convert and sanitize on render
            // Strip any <script> tags as extra precaution
            $data['markdown'] = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $data['markdown']);
        }

        // For html: this is intentionally raw but should only be editable by trusted users
        // We can add HTMLPurifier here if needed, but since it's restricted,
        // we trust the user has blocks.manage_html permission
        if ($type === 'html' && isset($data['html'])) {
            // Optional: strip <script> tags for extra safety
            // Or use HTMLPurifier for more thorough cleaning
            // For now, we trust the permission gate
        }

        return $data;
    }
}
