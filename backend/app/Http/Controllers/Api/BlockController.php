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
     * Responses are cache-friendly with ETag support.
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $preview = $request->boolean('preview', false);

        // If preview is requested, validate the signature
        if ($preview) {
            if (!config('blocks.preview.enabled')) {
                return response()->json(['message' => 'Preview not available'], 403);
            }

            // Validate signed URL
            if (!$request->hasValidSignature()) {
                return response()->json(['message' => 'Invalid or expired preview link'], 403);
            }

            // Fetch any status (draft or published)
            $block = Block::where('key', $key)->first();
        } else {
            // Only published blocks for public requests
            $cacheKey = "block:{$key}";

            if (config('blocks.cache.enabled')) {
                $block = Cache::remember($cacheKey, config('blocks.cache.ttl'), function () use ($key) {
                    return Block::where('key', $key)->published()->public()->first();
                });
            } else {
                $block = Block::where('key', $key)->published()->public()->first();
            }
        }

        if (!$block) {
            return response()->json(['message' => 'Block not found'], 404);
        }

        // Check visibility restrictions for non-preview requests
        if (!$preview && !$this->canAccessBlock($request, $block)) {
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

        // Set cache headers
        return response()->json($response)
            ->setEtag(md5($block->updated_at->timestamp))
            ->setLastModified($block->updated_at);
    }

    /**
     * List published blocks, optionally filtered by key prefix.
     *
     * Example: GET /api/content/blocks?prefix=homepage.
     */
    public function index(Request $request): JsonResponse
    {
        $prefix = $request->query('prefix');
        $limit = min((int) $request->query('limit', 50), 100);

        $query = Block::published()->public();

        if ($prefix) {
            $query->keyPrefix($prefix);
        }

        $blocks = $query->limit($limit)->get(['key', 'type', 'updated_at']);

        return response()->json([
            'blocks' => $blocks->map(fn($block) => [
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
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!config('blocks.preview.enabled')) {
            return response()->json(['message' => 'Preview not available'], 403);
        }

        $block = Block::where('key', $key)->first();

        if (!$block) {
            return response()->json(['message' => 'Block not found'], 404);
        }

        // Generate signed URL valid for the configured expiry time
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
        // Public blocks (visibility = null) are always accessible
        if ($block->isPublic()) {
            return true;
        }

        // "auth" visibility requires authenticated user
        if ($block->visibility === 'auth') {
            return $request->user() !== null;
        }

        // "role:xyz" visibility requires user with specific role/permission
        if (str_starts_with($block->visibility, 'role:')) {
            $role = substr($block->visibility, 5);
            $user = $request->user();

            if (!$user) {
                return false;
            }

            // Check if user has the required permission/role
            return $user->hasAccess($role) || $user->hasAccess("role.{$role}");
        }

        // Unknown visibility rule: deny access
        return false;
    }
}
