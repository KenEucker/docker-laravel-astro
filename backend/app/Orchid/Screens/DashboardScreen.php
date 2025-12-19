<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\User;
use App\Models\Setting;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
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
        return 'Welcome to the admin panel';
    }

    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'metrics' => [
                'users' => User::count(),
                'settings' => Setting::count(),
                'admins' => User::role('admin')->count(),
                'recent_user' => User::latest()->first(),
            ],
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        $metrics = $this->query()['metrics'];
        $recentUser = $metrics['recent_user'];

        return [
            Layout::view('orchid.dashboard.welcome', [
                'metrics' => $metrics,
            ]),

            Layout::columns([
                Layout::view('orchid.dashboard.metrics', [
                    'title' => 'Total Users',
                    'value' => $metrics['users'],
                    'icon' => 'bs.people',
                    'color' => 'primary',
                ]),
                Layout::view('orchid.dashboard.metrics', [
                    'title' => 'Admin Users',
                    'value' => $metrics['admins'],
                    'icon' => 'bs.shield-lock',
                    'color' => 'success',
                ]),
                Layout::view('orchid.dashboard.metrics', [
                    'title' => 'Settings',
                    'value' => $metrics['settings'],
                    'icon' => 'bs.gear',
                    'color' => 'info',
                ]),
            ]),

            Layout::view('orchid.dashboard.quick-links'),
        ];
    }
}
