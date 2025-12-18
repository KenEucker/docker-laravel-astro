<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        return User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email']);
    }

    public function show(User $user)
    {
        $user->load(['roles:id,name', 'permissions:id,name']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values(),
            'permissions' => $user->permissions->pluck('name')->values(),
        ];
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        // IMPORTANT: sync by name
        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['permissions'] ?? []);

        $user->load(['roles:id,name', 'permissions:id,name']);

        return [
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->permissions->pluck('name')->values(),
            ],
        ];
    }
}
