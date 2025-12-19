<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Orchid\Platform\Models\User as OrchidUser;
use Orchid\Screen\AsSource;

class User extends OrchidUser
{
    use HasApiTokens, HasFactory, Notifiable, AsSource;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::url($this->avatar_path) : null;
    }

    // Optional convenience helper (keep or delete—your call)
    public function grantAllAccess(): void
    {
        $this->permissions = ['*' => true];
        $this->save();
    }
}
