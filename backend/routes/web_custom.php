<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/admin/login');
});

Route::get('/dashboard', function () {
    return redirect('/admin/dashboard');
})->middleware('web');

// Debug route to check avatar paths (can be removed in production)
Route::middleware('auth:sanctum')->get('/debug/avatar/{user}', function (\App\Models\User $user) {
    return response()->json([
        'user_id' => $user->id,
        'name' => $user->name,
        'avatar_path' => $user->avatar_path,
        'avatar_url' => $user->avatar_url,
        'storage_url' => $user->avatar_path ? \Illuminate\Support\Facades\Storage::url($user->avatar_path) : null,
        'file_exists' => $user->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path) : null,
        'full_path' => $user->avatar_path ? storage_path('app/public/' . $user->avatar_path) : null,
    ]);
});