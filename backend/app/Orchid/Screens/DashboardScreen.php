<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\User;
use App\Models\Setting;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class DashboardScreen extends Screen
{
    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Dashboard';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Welcome to the admin panel, ' . auth()->user()->name;
    }

    /**
     * Query data.
     */
    public function query(): iterable
    {
        $recentUser = User::latest()->first();

        return [
            'metrics' => [
                'users' => number_format(User::count()),
                'admins' => number_format(User::role('admin')->count()),
                'settings' => number_format(Setting::count()),
            ],
            'recent_user' => $recentUser ? [
                'name' => $recentUser->name,
                'email' => $recentUser->email,
                'registered' => $recentUser->created_at->diffForHumans(),
            ] : null,
        ];
    }

    /**
     * Button commands (Quick Links).
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Manage Users'))
                ->icon('bs.people')
                ->route('platform.users.list')
                ->canSee(auth()->user()->hasPermissionTo('platform.users.list')),

            Link::make(__('Manage Settings'))
                ->icon('bs.gear')
                ->route('platform.settings.list')
                ->canSee(auth()->user()->hasPermissionTo('platform.settings.list')),

            Link::make(__('Roles & Permissions'))
                ->icon('bs.shield-lock')
                ->route('platform.systems.roles')
                ->canSee(auth()->user()->hasPermissionTo('platform.systems.roles')),

            Link::make(__('View Frontend'))
                ->icon('bs.box-arrow-up-right')
                ->href(config('app.frontend_url'))
                ->target('_blank'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        $layouts = [
            Layout::metrics([
                'Total Users'    => 'metrics.users',
                'Admin Users'    => 'metrics.admins',
                'Settings'       => 'metrics.settings',
            ]),
        ];

        // Add recent user info if exists
        if (request()->input('recent_user')) {
            $layouts[] = Layout::legend('recent_user', [
                Sight::make('name', __('Latest Registered User')),
                Sight::make('email', __('Email')),
                Sight::make('registered', __('Registered')),
            ])->title(__('Recent Activity'));
        }

        return $layouts;
    }
}
