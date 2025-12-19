<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Block Types Registry
    |--------------------------------------------------------------------------
    |
    | Define all allowed block types here. Each type specifies:
    | - label: Human-readable name
    | - description: What this block type is for
    | - restricted: Whether this type requires special permission (e.g., html)
    | - validation_rules: Laravel validation rules for the 'data' field
    |
    | Future consideration: Add schema_version field for future SSR/migration support
    |
    */

    'types' => [
        'hero' => [
            'label' => 'Hero Section',
            'description' => 'Large banner with headline, subheadline, image, and call-to-action buttons',
            'restricted' => false,
            'validation_rules' => [
                'headline' => 'required|string|max:255',
                'subheadline' => 'nullable|string|max:500',
                'image_url' => 'nullable|url|max:2048',
                'ctas' => 'nullable|array',
                'ctas.*.label' => 'required|string|max:100',
                'ctas.*.url' => 'required|url|max:2048',
            ],
        ],

        'richText' => [
            'label' => 'Rich Text',
            'description' => 'Markdown-formatted content (sanitized, no raw HTML)',
            'restricted' => false,
            'validation_rules' => [
                'markdown' => 'required|string|max:65535',
            ],
        ],

        'image' => [
            'label' => 'Image',
            'description' => 'Single image with optional caption',
            'restricted' => false,
            'validation_rules' => [
                'url' => 'required|url|max:2048',
                'alt' => 'nullable|string|max:255',
                'caption' => 'nullable|string|max:500',
            ],
        ],

        'cta' => [
            'label' => 'Call to Action',
            'description' => 'Prominent call-to-action with title, body, and button',
            'restricted' => false,
            'validation_rules' => [
                'title' => 'required|string|max:255',
                'body' => 'nullable|string|max:1000',
                'button_label' => 'required|string|max:100',
                'button_url' => 'required|url|max:2048',
                'variant' => 'nullable|in:primary,secondary',
            ],
        ],

        'featureGrid' => [
            'label' => 'Feature Grid',
            'description' => 'Grid of features with title, body, and optional link',
            'restricted' => false,
            'validation_rules' => [
                'title' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.title' => 'required|string|max:255',
                'items.*.body' => 'required|string|max:1000',
                'items.*.url' => 'nullable|url|max:2048',
            ],
        ],

        'html' => [
            'label' => 'Raw HTML',
            'description' => 'Raw HTML content (DANGEROUS - requires special permission)',
            'restricted' => true, // Requires blocks.manage_html permission
            'validation_rules' => [
                'html' => 'required|string|max:65535',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings for public block API responses
    |
    */

    'cache' => [
        'enabled' => env('BLOCKS_CACHE_ENABLED', true),
        'ttl' => env('BLOCKS_CACHE_TTL', 3600), // 1 hour default
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview Token Settings
    |--------------------------------------------------------------------------
    |
    | Settings for draft preview functionality
    |
    */

    'preview' => [
        'enabled' => env('BLOCKS_PREVIEW_ENABLED', true),
        'expiry' => env('BLOCKS_PREVIEW_EXPIRY', 3600), // 1 hour default
    ],
];
