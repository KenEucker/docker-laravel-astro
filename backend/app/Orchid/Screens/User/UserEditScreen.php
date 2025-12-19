<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserEditScreen extends Screen
{
    /**
     * @var User
     */
    public $user;

    /**
     * Query data.
     */
    public function query(User $user): iterable
    {
        // Ensure $this->user is populated for name/description/commandBar conditions
        $this->user = $user;

        return [
            'user' => $user,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->user->exists ? 'Edit User' : 'Create User';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->user->exists
            ? 'Update user information, password, and roles'
            : 'Create a new user with roles';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.users.edit',
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make(__('Remove'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this user?'))
                ->canSee(
                    $this->user->exists &&
                    auth()->user()?->hasAccess('platform.users.delete')
                ),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('user.name')
                    ->title('Name')
                    ->placeholder('Enter full name')
                    ->required(),

                Input::make('user.email')
                    ->type('email')
                    ->title('Email')
                    ->placeholder('user@example.com')
                    ->required(),

                Password::make('user.password')
                    ->title('Password')
                    ->placeholder($this->user->exists ? 'Leave blank to keep current password' : 'Enter password')
                    ->help($this->user->exists
                        ? 'Leave blank to keep the current password'
                        : 'Minimum 8 characters'),

                Picture::make('user.avatar_path')
                    ->title('Avatar')
                    ->storage('public')
                    ->acceptedFiles('image/*')
                    ->maxFileSize(5)
                    ->help('Maximum file size: 5MB'),

                // IMPORTANT:
                // Relation field submits Role IDs, not names.
                Relation::make('user.roles')
                    ->title('Roles')
                    ->fromModel(Role::class, 'name')
                    ->multiple()
                    ->help('Select one or more roles for this user'),
            ]),
        ];
    }

    /**
     * Save user.
     */
    public function save(Request $request, User $user): void
    {
        $validated = $request->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'user.password' => $user->exists
                ? 'nullable|string|min:8'
                : 'required|string|min:8',
            'user.avatar_path' => 'nullable|string',

            // Relation::make('user.roles') submits an array of role IDs
            'user.roles' => 'nullable|array',
            'user.roles.*' => 'integer|exists:roles,id',
        ]);

        $userData = $validated['user'];

        // Only update password if provided
        if (empty($userData['password'])) {
            unset($userData['password']);
        } else {
            $userData['password'] = Hash::make($userData['password']);
        }

        // Handle avatar update (delete old avatar if changed)
        if (!empty($userData['avatar_path'])) {
            if ($user->exists && $user->avatar_path && $user->avatar_path !== $userData['avatar_path']) {
                Storage::disk('public')->delete($user->avatar_path);
            }
        }

        // Save user
        $user->fill($userData);
        $user->save();

        // Sync roles (Orchid native)
        if (array_key_exists('roles', $validated['user'] ?? [])) {
            // If roles is null, sync to empty array to clear roles
            $roleIds = $validated['user']['roles'] ?? [];
            $user->roles()->sync($roleIds);
        }

        Toast::success(__('User saved successfully'));
    }

    /**
     * Remove user.
     */
    public function remove(User $user): void
    {
        // Delete avatar if exists
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        Toast::success(__('User deleted successfully'));
    }
}
