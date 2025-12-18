<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the application's default settings.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'APP_NAME',
                'value' => 'My Application',
                'type' => 'string',
                'description' => 'The name of the application displayed to users',
                'is_public' => true,
            ],
            [
                'key' => 'MAINTENANCE_MODE',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable or disable maintenance mode',
                'is_public' => true,
            ],
            [
                'key' => 'MAX_UPLOAD_SIZE',
                'value' => '10485760',
                'type' => 'integer',
                'description' => 'Maximum file upload size in bytes (default: 10MB)',
                'is_public' => false,
            ],
            [
                'key' => 'FEATURE_FLAGS',
                'value' => json_encode([
                    'new_dashboard' => false,
                    'advanced_analytics' => false,
                    'api_v2' => true,
                ]),
                'type' => 'json',
                'description' => 'Feature flags for enabling/disabling features',
                'is_public' => false,
            ],
            [
                'key' => 'CONTACT_EMAIL',
                'value' => 'admin@example.com',
                'type' => 'string',
                'description' => 'Contact email for support inquiries',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}
