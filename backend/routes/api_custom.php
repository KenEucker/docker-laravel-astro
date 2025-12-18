<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminAclController;

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
  $u = $request->user();

  return [
    'id' => $u->id,
    'name' => $u->name,
    'email' => $u->email,
    'roles' => $u->roles->pluck('name')->values(),
    'permissions' => $u->permissions->pluck('name')->values(),
    'created_at' => $u->created_at,
  ];
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  $u = $request->user();

  return [
    'id' => $u->id,
    'name' => $u->name,
    'email' => $u->email,
    'created_at' => $u->created_at,
  ];
});

Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {
  Route::get('/users', [AdminUserController::class, 'index']);
  Route::get('/users/{user}', [AdminUserController::class, 'show']);
  Route::put('/users/{user}', [AdminUserController::class, 'update']);

  Route::get('/roles', [AdminAclController::class, 'roles']);
  Route::get('/permissions', [AdminAclController::class, 'permissions']);
});
