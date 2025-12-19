<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class OrchidPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are stored as key=>bool maps on roles/users and checked via hasAccess().
     */
    public function run(): void
    {
        /**
         * Keep these exact keys to preserve existing/expected Orchid functionality.
         * (These are "platform" permissions used by the admin panel side.)
         */
        $platformPermissions = [
            'platform.systems.roles' => true,

            // Users
            'platform.users.list'   => true,  // legacy
            'platform.users.view'   => true,  // new
            'platform.users.create' => true,  // new
            'platform.users.edit'   => true,
            'platform.users.delete' => true,

            // Settings
            'platform.settings.list'   => true, // legacy
            'platform.settings.view'   => true, // new
            'platform.settings.create' => true, // new
            'platform.settings.edit'   => true,
            'platform.settings.delete' => true,
        ];


        /**
         * API / end-user permissions (your application vocabulary).
         * These are optional right now, but useful for gating your /api/admin routes
         * and any future API authorization rules.
         */
        $apiPermissions = [
            'app.admin' => true,

            // Users CRUD
            'app.users.view'   => true,
            'app.users.create' => true,
            'app.users.edit'   => true,

            // Settings CRUD
            'app.settings.view'   => true,
            'app.settings.create' => true,
            'app.settings.edit'   => true,
        ];

        /**
         * Admin role = global + explicit keys (stageable).
         * Keeping '*' => true ensures you won't lock yourself out while permissions evolve.
         */
        $adminPermissions = array_merge(
            ['*' => true],
            $platformPermissions,
            $apiPermissions,
        );

        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name'        => 'Admin',
                'permissions' => $adminPermissions,
            ]
        );

        $this->command?->info("Orchid admin role ensured: {$adminRole->slug}");
        $this->command?->info('Platform permissions + API permissions granted (including wildcard).');
    }
}
