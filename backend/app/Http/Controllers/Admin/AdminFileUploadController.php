<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Custom file upload controller that mimics Orchid's attachment system
 * but with custom authorization for avatar uploads
 */
class AdminFileUploadController extends Controller
{
    /**
     * Handle file upload (mimics Orchid's /admin/systems/files endpoint)
     */
    public function upload(Request $request)
    {
        // Check if user has platform access
        $user = $request->user();
        if (!$user || !$user->hasAccess('platform.users.edit')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();

        // Store in a generic avatars folder
        // The file will be moved to user-specific folder when the form is saved
        $dir = 'avatars/temp';
        $filename = Str::uuid()->toString() . '.' . $ext;
        $path = $file->storeAs($dir, $filename, 'public');

        // Return response in the format Orchid expects
        return response()->json([
            'url' => Storage::url($path),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'disk' => 'public',
        ]);
    }
}
