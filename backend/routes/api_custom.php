<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminAclController;
use App\Http\Controllers\User\UserAvatarController;

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
  $u = $request->user();

  return [
    'id' => $u->id,
    'name' => $u->name,
    'email' => $u->email,
    'avatar_path' => $u->avatar_path,
    'avatar_url' => $u->avatar_url,
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

Route::middleware('auth:sanctum')->group(function () {
  Route::put('/user/avatar', [UserAvatarController::class, 'store']);
  Route::delete('/user/avatar', [UserAvatarController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {
  Route::get('/users', [AdminUserController::class, 'index']);
  Route::get('/users/{user}', [AdminUserController::class, 'show']);
  Route::put('/users/{user}', [AdminUserController::class, 'update']);

  Route::get('/roles', [AdminAclController::class, 'roles']);
  Route::get('/permissions', [AdminAclController::class, 'permissions']);
});
