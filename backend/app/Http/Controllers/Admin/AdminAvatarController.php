<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminAvatarController extends Controller
{
    /**
     * Upload avatar for a specific user (admin use)
     */
    public function store(Request $request, User $user)
    {
        // Check if user has permission to edit users
        if (!$request->user()->hasAccess('platform.users.edit')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
        ]);

        $file = $request->file('avatar');
        $ext = $file->getClientOriginalExtension();

        // Keep a tidy per-user folder, same as frontend
        $dir = 'avatars/' . $user->id;

        // Use a uuid filename to avoid collisions
        $filename = Str::uuid()->toString() . '.' . $ext;

        // Delete old avatar if exists
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Store the new avatar
        $path = $file->storeAs($dir, $filename, 'public');

        // Update user's avatar_path
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'avatar_path' => $path,
            'avatar_url' => Storage::url($path),
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    /**
     * Delete avatar for a specific user (admin use)
     */
    public function destroy(Request $request, User $user)
    {
        // Check if user has permission to edit users
        if (!$request->user()->hasAccess('platform.users.edit')) {
            abort(403, 'Unauthorized');
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Avatar deleted successfully',
        ]);
    }
}
