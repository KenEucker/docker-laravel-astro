<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Setting;
use App\Models\User;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class DashboardScreen extends Screen
{
    public function name(): ?string
    {
        return 'Dashboard';
    }

    public function description(): ?string
    {
        $name = auth()->user()?->name ?? 'User';
        return 'Welcome to the admin panel, ' . $name;
    }

    public function query(): iterable
    {
        $recentUser = User::query()->latest()->first();

        // Count “admins” as users with global wildcard permission.
        // This matches your DefaultAdminUserSeeder which sets ['*' => true].
        $adminCount = User::query()
            ->whereJsonContains('permissions->*', true)
            ->count();

        return [
            'metrics' => [
                'users' => number_format(User::query()->count()),
                'admins' => number_format($adminCount),
                'settings' => number_format(Setting::query()->count()),
            ],
            'recent_user' => $recentUser ? [
                'name' => $recentUser->name,
                'email' => $recentUser->email,
                'registered' => optional($recentUser->created_at)->diffForHumans(),
            ] : null,
        ];
    }

    public function commandBar(): iterable
    {
        $u = auth()->user();

        return [
            Link::make(__('Manage Users'))
                ->icon('bs.people')
                ->route('platform.users.list')
                ->canSee($u?->hasAccess('platform.users.list') ?? false),

            Link::make(__('Manage Settings'))
                ->icon('bs.gear')
                ->route('platform.settings.list')
                ->canSee($u?->hasAccess('platform.settings.list') ?? false),

            Link::make(__('Roles & Permissions'))
                ->icon('bs.shield-lock')
                ->route('platform.systems.roles')
                ->canSee($u?->hasAccess('platform.systems.roles') ?? false),

            Link::make(__('View Frontend'))
                ->icon('bs.box-arrow-up-right')
                ->href(config('app.frontend_url'))
                ->target('_blank'),
        ];
    }

    public function layout(): iterable
    {
        $layouts = [
            Layout::metrics([
                'Total Users' => 'metrics.users',
                'Admin Users' => 'metrics.admins',
                'Settings'    => 'metrics.settings',
            ]),
        ];

        // Show recent user legend if the query provided data
        if (!empty($this->query['recent_user'] ?? null)) {
            $layouts[] = Layout::legend('recent_user', [
                Sight::make('name', __('Latest Registered User')),
                Sight::make('email', __('Email')),
                Sight::make('registered', __('Registered')),
            ])->title(__('Recent Activity'));
        }

        return $layouts;
    }
}
