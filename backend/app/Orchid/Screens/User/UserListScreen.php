<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\User;
use App\Orchid\Layouts\User\UserListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class UserListScreen extends Screen
{
    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Users';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage all users';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.users.list',
        ];
    }

    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'users' => User::with('roles')
                ->filters()
                ->defaultSort('created_at', 'desc')
                ->paginate(15),
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create User'))
                ->icon('bs.plus-circle')
                ->route('platform.users.create')
                ->canSee(auth()->user()->hasPermissionTo('platform.users.edit')),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            UserListLayout::class,
        ];
    }
}
