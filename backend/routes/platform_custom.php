<?php

declare(strict_types=1);

use App\Orchid\Screens\DashboardScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\Setting\SettingEditScreen;
use App\Orchid\Screens\Setting\SettingListScreen;
use App\Http\Controllers\Admin\AdminAvatarController;
use App\Http\Controllers\Admin\AdminFileUploadController;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Main Dashboard
Route::screen('/dashboard', DashboardScreen::class)
    ->name('platform.dashboard')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->push(__('Dashboard'), route('platform.dashboard')));

// User Management
Route::screen('users', UserListScreen::class)
    ->name('platform.users.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.dashboard')
        ->push(__('Users'), route('platform.users.list')));

Route::screen('users/create', UserEditScreen::class)
    ->name('platform.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.users.list')
        ->push(__('Create User')));

Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.users.edit')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.users.list')
        ->push(__('Edit User')));

// Settings Management
Route::screen('settings', SettingListScreen::class)
    ->name('platform.settings.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.dashboard')
        ->push(__('Settings'), route('platform.settings.list')));

Route::screen('settings/create', SettingEditScreen::class)
    ->name('platform.settings.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.settings.list')
        ->push(__('Create Setting')));

Route::screen('settings/{setting}/edit', SettingEditScreen::class)
    ->name('platform.settings.edit')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.settings.list')
        ->push(__('Edit Setting')));

// Override Orchid's file upload endpoint with custom authorization
Route::post('systems/files', [AdminFileUploadController::class, 'upload'])
    ->name('platform.systems.files.upload');

// Admin Avatar Upload Routes (requires platform.users.edit permission)
Route::post('users/{user}/avatar', [AdminAvatarController::class, 'store'])
    ->name('platform.users.avatar.upload');
Route::delete('users/{user}/avatar', [AdminAvatarController::class, 'destroy'])
    ->name('platform.users.avatar.delete');
