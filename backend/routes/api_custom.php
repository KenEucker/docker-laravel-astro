<?php

use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\UserNoteController;
use App\Http\Controllers\User\UserAvatarController;
use App\Http\Middleware\OrchidAdminAccess;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public settings endpoint (no auth required)
Route::get('/settings/public', function () {
    return Setting::all(true);
});

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $u = $request->user();

    return [
        'id' => $u->id,
        'name' => $u->name,
        'email' => $u->email,
        'avatar_path' => $u->avatar_path,
        'avatar_url' => $u->avatar_url,
        'is_admin' => $u->hasAccess('app.admin') || $u->hasAccess('*'),

        // Orchid: permissions are generally stored as keys (often in users.permissions JSON)
        // and inherited via roles.
        'permissions' => $u->permissions ?? [],

        // If your frontend expects `roles`, you can later populate this once your User model
        // exposes Orchid roles reliably in your overlay setup.
        'roles' => [],

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
    Route::post('/user/avatar', [UserAvatarController::class, 'store']);
    Route::delete('/user/avatar', [UserAvatarController::class, 'destroy']);

    // User Notes (personal notes for authenticated users)
    Route::get('/me/notes', [UserNoteController::class, 'index']);
    Route::post('/me/notes', [UserNoteController::class, 'store']);
    Route::get('/me/notes/{note}', [UserNoteController::class, 'show']);
    Route::put('/me/notes/{note}', [UserNoteController::class, 'update']);
    Route::post('/me/notes/{note}/archive', [UserNoteController::class, 'archive']);
    Route::post('/me/notes/{note}/unarchive', [UserNoteController::class, 'unarchive']);
    Route::delete('/me/notes/{note}', [UserNoteController::class, 'destroy']);

    // Block preview URLs (authenticated only)
    Route::get('/content/blocks/{key}/preview', [BlockController::class, 'preview'])->name('api.blocks.preview');
});

/**
 * Admin API routes
 *
 * No Kernel.php alias: use middleware class name directly (Option B).
 * Gate everything behind an Orchid permission key, e.g. 'app.admin'.
 */
Route::middleware([
    'auth:sanctum',
    OrchidAdminAccess::class.':app.admin',
])->prefix('admin')->group(function () {

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);

    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::get('/settings/{setting}', [AdminSettingController::class, 'show']);
    Route::post('/settings', [AdminSettingController::class, 'store']);
    Route::put('/settings/{setting}', [AdminSettingController::class, 'update']);
    Route::delete('/settings/{setting}', [AdminSettingController::class, 'destroy']);
    Route::get('/settings/{key}/value', [AdminSettingController::class, 'getValue']);
});

/**
 * Public Content API (blocks)
 *
 * These endpoints are public and serve published content to the frontend.
 * No authentication required for published blocks.
 */
Route::prefix('content')->group(function () {
    Route::get('/blocks', [BlockController::class, 'index']);
    Route::get('/blocks/{key}', [BlockController::class, 'show'])->name('api.blocks.show');
});
