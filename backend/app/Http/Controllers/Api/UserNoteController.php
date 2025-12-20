<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * User Notes API Controller
 *
 * Manages personal notes for authenticated users.
 * Notes are scoped to the current user and not visible to others.
 */
class UserNoteController extends Controller
{
    /**
     * Get all notes for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status'); // 'active', 'archived', or null for all

        $query = UserNote::forUser($request->user()->id)
            ->orderBy('updated_at', 'desc');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'archived') {
            $query->archived();
        }

        $notes = $query->get();

        return response()->json([
            'notes' => $notes->map(fn ($note) => [
                'id' => $note->id,
                'title' => $note->title,
                'body' => $note->body,
                'status' => $note->status,
                'created_at' => $note->created_at->toIso8601String(),
                'updated_at' => $note->updated_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get a specific note.
     */
    public function show(Request $request, UserNote $note): JsonResponse
    {
        // Ensure user owns this note
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'body' => $note->body,
            'status' => $note->status,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Create a new note.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $note = UserNote::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'status' => 'active',
        ]);

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'body' => $note->body,
            'status' => $note->status,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ], 201);
    }

    /**
     * Update an existing note.
     */
    public function update(Request $request, UserNote $note): JsonResponse
    {
        // Ensure user owns this note
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $note->update($request->only(['title', 'body']));

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'body' => $note->body,
            'status' => $note->status,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Archive a note.
     */
    public function archive(Request $request, UserNote $note): JsonResponse
    {
        // Ensure user owns this note
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $note->archive();

        return response()->json([
            'message' => 'Note archived successfully',
            'id' => $note->id,
            'status' => $note->status,
        ]);
    }

    /**
     * Unarchive a note.
     */
    public function unarchive(Request $request, UserNote $note): JsonResponse
    {
        // Ensure user owns this note
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $note->unarchive();

        return response()->json([
            'message' => 'Note unarchived successfully',
            'id' => $note->id,
            'status' => $note->status,
        ]);
    }

    /**
     * Delete a note permanently.
     */
    public function destroy(Request $request, UserNote $note): JsonResponse
    {
        // Ensure user owns this note
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully',
        ]);
    }
}
