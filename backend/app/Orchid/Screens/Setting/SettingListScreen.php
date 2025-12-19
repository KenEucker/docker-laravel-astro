<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Setting;

use App\Models\Setting;
use App\Orchid\Layouts\Setting\SettingListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class SettingListScreen extends Screen
{
    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Settings';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage application settings';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.settings.list',
        ];
    }

    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'settings' => Setting::filters()
                ->defaultSort('key', 'asc')
                ->paginate(20),
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create Setting'))
                ->icon('bs.plus-circle')
                ->route('platform.settings.create')
                ->canSee(auth()->user()->hasAccess('platform.settings.edit')),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            SettingListLayout::class,
        ];
    }
}
