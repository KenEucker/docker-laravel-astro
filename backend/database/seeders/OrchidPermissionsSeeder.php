<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class OrchidPermissionsSeeder extends Seeder
{
    /**
     * Single source of truth for the admin permission map.
     * Anything you add here is available to both:
     * - the admin Role
     * - the default admin User (if you choose to assign directly)
     */
    public static function adminPermissions(): array
    {
        $platformPermissions = [
            // Core platform entry (these matter for the 403)
            'platform.index' => true,
            'platform.main' => true,
            'platform.dashboard' => true,
            'platform.profile' => true,
            'platform.search' => true,

            // Roles
            'platform.systems.roles' => true,

            // Users
            'platform.users.list' => true,
            'platform.users.view' => true,
            'platform.users.create' => true,
            'platform.users.edit' => true,
            'platform.users.delete' => true,

            // Settings
            'platform.settings.list' => true,
            'platform.settings.view' => true,
            'platform.settings.create' => true,
            'platform.settings.edit' => true,
            'platform.settings.delete' => true,

            // Content
            'platform.systems.attachment' => true,
            'platform.blocks.view' => true,
            'platform.blocks.create' => true,
            'platform.blocks.edit' => true,
            'platform.blocks.publish' => true,
            'platform.blocks.delete' => true,
            'platform.blocks.manage_html' => true,
            'platform.blocks.manage_locked' => true,
            'platform.blocks.manage_visibility' => true,
        ];

        $apiPermissions = [
            'app.admin' => true,

            'app.users.view' => true,
            'app.users.create' => true,
            'app.users.edit' => true,

            'app.settings.view' => true,
            'app.settings.create' => true,
            'app.settings.edit' => true,
        ];

        // Return one merged permission map
        return array_merge($platformPermissions, $apiPermissions);
    }

    public function run(): void
    {
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'permissions' => self::adminPermissions(),
            ]
        );

        $this->command?->info("Orchid admin role ensured: {$adminRole->slug}");
    }
}
