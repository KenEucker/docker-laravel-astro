<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    /**
     * Register the application menu.
     *
     * @return array
     */
    public function registerMenu(): array
    {
        return [
            Menu::make(__('Dashboard'))
                ->icon('bs.speedometer2')
                ->route('platform.dashboard')
                ->title(__('Navigation')),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.users.list')
                ->permission('platform.users.list'),

            Menu::make(__('Settings'))
                ->icon('bs.gear')
                ->route('platform.settings.list')
                ->permission('platform.settings.list'),

            Menu::make(__('Roles & Permissions'))
                ->icon('bs.shield-lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles')),

            ItemPermission::group(__('Users'))
                ->addPermission('platform.users.list', __('View Users'))
                ->addPermission('platform.users.edit', __('Edit Users'))
                ->addPermission('platform.users.delete', __('Delete Users')),

            ItemPermission::group(__('Settings'))
                ->addPermission('platform.settings.list', __('View Settings'))
                ->addPermission('platform.settings.edit', __('Edit Settings'))
                ->addPermission('platform.settings.delete', __('Delete Settings')),
        ];
    }
}
