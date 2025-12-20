<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    /**
     * Register the application menu.
     */
    public function registerMenu(): array
    {
        return [
            Menu::make(__('Dashboard'))
                ->icon('bs.speedometer2')
                ->route('platform.dashboard')
                ->title(__('Navigation')),

            Menu::make(__('Blocks'))
                ->icon('bs.grid-3x3-gap')
                ->route('platform.blocks.list')
                ->permission('platform.blocks.view')
                ->title(__('Content')),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.users.list')
                ->permission('platform.users.list')
                ->title(__('System')),

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

            ItemPermission::group(__('Content Blocks'))
                ->addPermission('platform.blocks.view', __('View Blocks'))
                ->addPermission('platform.blocks.create', __('Create Blocks'))
                ->addPermission('platform.blocks.edit', __('Edit Blocks'))
                ->addPermission('platform.blocks.publish', __('Publish Blocks'))
                ->addPermission('platform.blocks.delete', __('Delete Blocks'))
                ->addPermission('platform.blocks.manage_html', __('Manage HTML Blocks'))
                ->addPermission('platform.blocks.manage_locked', __('Manage Locked Blocks'))
                ->addPermission('platform.blocks.manage_visibility', __('Manage Block Visibility')),

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
