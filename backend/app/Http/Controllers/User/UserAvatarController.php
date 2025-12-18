<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarController extends Controller
{
  public function store(Request $request)
  {
    $user = $request->user();

    $validated = $request->validate([
      'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
    ]);

    // Delete old avatar if present
    if ($user->avatar_path) {
      Storage::disk('public')->delete($user->avatar_path);
    }

    $file = $validated['avatar'];

    // Keep a tidy per-user folder
    $dir = 'avatars/' . $user->id;

    // Use a uuid filename to avoid collisions
    $ext = $file->getClientOriginalExtension() ?: 'jpg';
    $filename = Str::uuid()->toString() . '.' . $ext;

    $path = $file->storeAs($dir, $filename, 'public');

    $user->avatar_path = $path;
    $user->save();

    return response()->json([
      'avatar_path' => $user->avatar_path,
      'avatar_url' => $user->avatar_url,
      'user' => $user,
    ]);
  }

  public function destroy(Request $request)
  {
    $user = $request->user();

    if ($user->avatar_path) {
      Storage::disk('public')->delete($user->avatar_path);
      $user->avatar_path = null;
      $user->save();
    }

    return response()->json([
      'avatar_url' => null,
      'user' => $user,
    ]);
  }
}
