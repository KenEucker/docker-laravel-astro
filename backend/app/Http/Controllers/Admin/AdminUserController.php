<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Platform\Models\Role;

class AdminUserController extends Controller
{
    public function index()
    {
        return User::query()
            ->with(['roles:id,slug,name'])
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'permissions'])
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->roles->pluck('slug')->values(),          // Orchid roles
                    'permissions' => array_keys(array_filter($u->permissions ?? [])), // direct user perms (keys)
                    'is_admin' => $u->hasAccess('app.admin'),
                ];
            })
            ->values();
    }

    public function show(User $user)
    {
        $user->load(['roles:id,slug,name']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,

            // Prefer slugs for stability, but you can swap to ->pluck('name') if your UI expects names
            'roles' => $user->roles->pluck('slug')->values(),

            // Direct permissions are stored as a map: ['key' => true]
            // Return enabled keys to mimic old "list of permission names"
            'permissions' => array_keys(array_filter($user->permissions ?? [])),

            'is_admin' => $user->hasAccess('app.admin'),
        ];
    }

    /**
     * Optional: If you still need editing from the frontend.
     *
     * - roles: accept role slugs (recommended) or IDs (adjust validation accordingly)
     * - permissions: accept an array of permission keys (strings) and store them as key=>true
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string'], // role slugs
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'], // permission keys
        ]);

        // Sync roles by slug (Orchid native Role model)
        if (array_key_exists('roles', $data)) {
            $roleIds = Role::query()
                ->whereIn('slug', $data['roles'] ?? [])
                ->pluck('id')
                ->all();

            $user->roles()->sync($roleIds);
        }

        // Store direct user permissions as key => true
        if (array_key_exists('permissions', $data)) {
            $perms = [];
            foreach (($data['permissions'] ?? []) as $key) {
                $perms[$key] = true;
            }
            $user->permissions = $perms;
            $user->save();
        }

        $user->load(['roles:id,slug,name']);

        return [
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('slug')->values(),
                'permissions' => array_keys(array_filter($user->permissions ?? [])),
                'is_admin' => $user->hasAccess('app.admin'),
            ],
        ];
    }
}
