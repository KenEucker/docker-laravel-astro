<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'users';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => $user->id),

            TD::make('name', 'Name')
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => Link::make($user->name)
                    ->route('platform.users.edit', $user)),

            TD::make('email', 'Email')
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => $user->email),

            TD::make('roles', 'Roles')
                ->render(fn (User $user) => implode(', ', $user->roles->pluck('name')->toArray())),

            TD::make('created_at', 'Created')
                ->sort()
                ->render(fn (User $user) => $user->created_at->format('M d, Y')),

            TD::make('actions', 'Actions')
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (User $user) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->route('platform.users.edit', $user)
                            ->canSee(auth()->user()->hasPermissionTo('platform.users.edit')),

                        Button::make(__('Delete'))
                            ->icon('bs.trash')
                            ->method('remove')
                            ->confirm(__('Are you sure you want to delete this user?'))
                            ->parameters(['id' => $user->id])
                            ->canSee(auth()->user()->hasPermissionTo('platform.users.delete')),
                    ])),
        ];
    }
}
