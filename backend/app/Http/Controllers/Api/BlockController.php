<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Public API controller for content blocks.
 * Serves published blocks to the frontend, with preview support via signed URLs.
 */
class BlockController extends Controller
{
    /**
     * Get a single block by key.
     *
     * Returns published blocks only, unless a valid preview signature is provided.
     * For non-preview requests, visibility is enforced:
     *  - public blocks are accessible to all
     *  - auth/role blocks require an authenticated user (and access rules)
     *
     * Responses are cache-friendly with ETag support.
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $preview = $request->boolean('preview', false);

        // Preview requests: validate signature and return any status (draft/published)
        if ($preview) {
            if (! config('blocks.preview.enabled')) {
                return response()->json(['message' => 'Preview not available'], 403);
            }

            if (! $request->hasValidSignature()) {
                return response()->json(['message' => 'Invalid or expired preview link'], 403);
            }

            $block = Block::where('key', $key)->first();
        } else {
            // Non-preview: only published blocks, but DO NOT filter by visibility here.
            // We must fetch the block first, then enforce visibility via canAccessBlock().
            // Otherwise "auth" blocks can never be returned even to authenticated users.

            $userId = $request->user()?->id ?? 0;

            // Cache strategy:
            // - Public blocks: shared cache key (safe for everyone)
            // - Restricted blocks: user-scoped cache key (prevents leakage)
            //
            // NOTE: We don't know visibility until we've loaded the block once.
            // We'll do a small "read-through" flow: fetch uncached first if needed.
            $block = null;

            if (config('blocks.cache.enabled')) {
                // Try shared public cache first (fast path)
                $publicCacheKey = "block:{$key}:public";

                $block = Cache::get($publicCacheKey);

                if (! $block) {
                    // Fetch published block (any visibility)
                    $block = Block::where('key', $key)->published()->first();

                    if ($block) {
                        if ($block->isPublic()) {
                            Cache::put($publicCacheKey, $block, config('blocks.cache.ttl'));
                        } else {
                            // Restricted: cache per-user to avoid leaking access
                            $userCacheKey = "block:{$key}:user:{$userId}";
                            Cache::put($userCacheKey, $block, config('blocks.cache.ttl'));
                        }
                    }
                } else {
                    // If we hit the public cache, great.
                    // If this key is actually restricted, it wouldn't be in public cache.
                }

                // If not public cached and we have a user, try user cache for restricted blocks
                if (! $block && $userId) {
                    $userCacheKey = "block:{$key}:user:{$userId}";
                    $block = Cache::remember($userCacheKey, config('blocks.cache.ttl'), function () use ($key) {
                        return Block::where('key', $key)->published()->first();
                    });
                }

                // If still null (public cache miss + no user cache hit), just load it
                if (! $block) {
                    $block = Block::where('key', $key)->published()->first();
                }
            } else {
                $block = Block::where('key', $key)->published()->first();
            }
        }

        if (! $block) {
            return response()->json(['message' => 'Block not found'], 404);
        }

        // Enforce visibility restrictions for non-preview requests
        if (! $preview && ! $this->canAccessBlock($request, $block)) {
            // Intentionally 404 to avoid leaking existence
            return response()->json(['message' => 'Block not found'], 404);
        }

        // Build response
        $response = [
            'key' => $block->key,
            'type' => $block->type,
            'data' => $block->data,
            'updated_at' => $block->updated_at->toIso8601String(),
        ];

        // Include preview-only fields
        if ($preview) {
            $response['status'] = $block->status;
            $response['published_at'] = $block->published_at?->toIso8601String();
        }

        return response()->json($response)
            ->setEtag(md5((string) $block->updated_at->timestamp))
            ->setLastModified($block->updated_at);
    }

    /**
     * List published public blocks, optionally filtered by key prefix.
     *
     * Example: GET /api/content/blocks?prefix=homepage.
     */
    public function index(Request $request): JsonResponse
    {
        $prefix = $request->query('prefix');
        $limit = min((int) $request->query('limit', 50), 100);

        // Listing remains PUBLIC only (safe and intentional)
        $query = Block::published()->public();

        if ($prefix) {
            $query->keyPrefix($prefix);
        }

        $blocks = $query->limit($limit)->get(['key', 'type', 'updated_at']);

        return response()->json([
            'blocks' => $blocks->map(fn ($block) => [
                'key' => $block->key,
                'type' => $block->type,
                'updated_at' => $block->updated_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Generate a preview URL for a block (authenticated users only).
     */
    public function preview(Request $request, string $key): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! config('blocks.preview.enabled')) {
            return response()->json(['message' => 'Preview not available'], 403);
        }

        $block = Block::where('key', $key)->first();

        if (! $block) {
            return response()->json(['message' => 'Block not found'], 404);
        }

        $expiry = now()->addSeconds(config('blocks.preview.expiry', 3600));

        $url = URL::temporarySignedRoute(
            'api.blocks.show',
            $expiry,
            ['key' => $key, 'preview' => 1]
        );

        return response()->json([
            'preview_url' => $url,
            'expires_at' => $expiry->toIso8601String(),
        ]);
    }

    /**
     * Check if the current request can access a block based on visibility rules.
     */
    protected function canAccessBlock(Request $request, Block $block): bool
    {
        if ($block->isPublic()) {
            return true;
        }

        if ($block->visibility === 'auth') {
            return $request->user() !== null;
        }

        if (is_string($block->visibility) && str_starts_with($block->visibility, 'role:')) {
            $role = substr($block->visibility, 5);
            $user = $request->user();

            if (! $user) {
                return false;
            }

            return $user->hasAccess($role) || $user->hasAccess("role.{$role}");
        }

        return false;
    }
}
