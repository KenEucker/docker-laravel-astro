<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Orchid\Platform\Models\Role;

class DefaultAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DEFAULT_USER_EMAIL');
        $password = env('DEFAULT_USER_PASSWORD');
        $name = env('DEFAULT_USER_NAME', 'Admin');

        if (! $email || ! $password) {
            $this->command?->warn('DEFAULT_USER_EMAIL / DEFAULT_USER_PASSWORD missing; skipping DefaultAdminUserSeeder.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
            ]
        );

        // Give the user ALL permissions defined in OrchidPermissionsSeeder
        $user->permissions = OrchidPermissionsSeeder::adminPermissions();
        $user->save();

        // Attach the admin role too (optional, but nice for UI/clarity)
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $this->command?->info("Default admin user ensured: {$email}");
    }
}
