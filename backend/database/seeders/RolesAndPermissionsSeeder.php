<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Example permissions
    $permissions = [
      'admin.view',
      'admin.manage-users',
    ];

    foreach ($permissions as $p) {
      Permission::firstOrCreate(['name' => $p]);
    }

    $admin = Role::firstOrCreate(['name' => 'admin']);
    $user  = Role::firstOrCreate(['name' => 'user']);

    $admin->syncPermissions($permissions);
    $user->syncPermissions([]); // regular users get none by default
  }
}
