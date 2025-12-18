<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminAclController extends Controller
{
    public function roles()
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    public function permissions()
    {
        return Permission::query()->orderBy('name')->get(['id', 'name']);
    }
}
