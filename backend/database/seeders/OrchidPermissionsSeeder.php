<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OrchidPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Orchid permissions
        $permissions = [
            // System permissions
            'platform.systems.roles' => 'Access roles and permissions management',

            // User permissions
            'platform.users.list' => 'View users list',
            'platform.users.edit' => 'Create and edit users',
            'platform.users.delete' => 'Delete users',

            // Settings permissions
            'platform.settings.list' => 'View settings list',
            'platform.settings.edit' => 'Create and edit settings',
            'platform.settings.delete' => 'Delete settings',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Assign all Orchid permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $orchidPermissions = Permission::whereIn('name', array_keys($permissions))->get();
            $adminRole->syncPermissions(
                $adminRole->permissions->merge($orchidPermissions)->unique('id')
            );

            $this->command->info('Orchid permissions assigned to admin role');
        }

        $this->command->info('Orchid permissions created successfully');
    }
}
