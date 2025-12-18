<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DefaultAdminUserSeeder extends Seeder
{
  public function run(): void
  {
    // Clear Spatie permission cache
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $email = env('DEFAULT_USER_EMAIL');
    $password = env('DEFAULT_USER_PASSWORD');
    $name = env('DEFAULT_USER_NAME', 'Admin');

    if (!$email || !$password) {
      $this->command?->warn('DEFAULT_USER_EMAIL/DEFAULT_USER_PASSWORD missing; skipping DefaultAdminUserSeeder.');
      return;
    }

    // Ensure the role exists even if RolesAndPermissionsSeeder didn’t run
    Role::firstOrCreate(['name' => 'admin']);

    $user = User::updateOrCreate(
      ['email' => $email],
      [
        'name' => $name,
        'password' => Hash::make($password),
      ]
    );

    // Assign role (sync is idempotent)
    if (method_exists($user, 'syncRoles')) {
      $user->syncRoles(['admin']);
    } else {
      $this->command?->warn('User model missing HasRoles trait; cannot assign admin role.');
    }

    $this->command?->info("Default admin user ensured + role assigned: {$email}");
  }
}
