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

        if (!$email || !$password) {
            $this->command?->warn('DEFAULT_USER_EMAIL / DEFAULT_USER_PASSWORD missing; skipping DefaultAdminUserSeeder.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        // Ensure the user will always have access during staged setup.
        $user->permissions = array_merge($user->permissions ?? [], ['*' => true]);
        $user->save();

        // Attach Orchid "admin" role if it exists (idempotent)
        try {
            $adminRole = Role::where('slug', 'admin')->first();

            if ($adminRole) {
                // Orchid roles relation exists on Orchid's User model; in your overlay setup it may not.
                // Safest approach: attach via relation if present, otherwise skip with a warning.
                if (method_exists($user, 'roles')) {
                    $user->roles()->syncWithoutDetaching([$adminRole->id]);
                    $this->command?->info("Admin role attached to user: {$email}");
                } else {
                    $this->command?->warn("User model has no roles() relation; admin role not attached (user still has '*' access).");
                }
            } else {
                $this->command?->warn("Admin role (slug=admin) not found; user still has '*' access.");
            }
        } catch (\Throwable $e) {
            $this->command?->warn("Failed attaching admin role; user still has '*' access. {$e->getMessage()}");
        }

        $this->command?->info("Default admin user ensured: {$email}");
    }
}
