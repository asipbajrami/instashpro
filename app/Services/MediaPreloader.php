<?php

namespace App\Services;

use App\Models\InstagramMedia;
use Illuminate\Support\Collection;

/**
 * Pre-loads Instagram media for a collection of products to avoid N+1 queries.
 *
 * This service batch-loads all media needed by ProductResource in 2 queries
 * instead of 5-7 queries per product.
 */
class MediaPreloader
{
    /**
     * Cache of media indexed by ID
     */
    private static array $mediaCache = [];

    /**
     * Cache of media indexed by post_id -> type -> media_id
     * Used for finding high-quality variants
     */
    private static array $postMediaCache = [];

    /**
     * Pre-load all media for a collection of products.
     * Call this before passing products to ProductResource.
     */
    public static function preloadForProducts(Collection $products): void
    {
        // Clear any stale cache from previous requests
        self::clear();

        // 1. Collect all media IDs from all products
        $allMediaIds = [];

        foreach ($products as $product) {
            if (!empty($product->instagram_media_ids)) {
                $ids = array_filter(explode('_', $product->instagram_media_ids));
                $allMediaIds = array_merge($allMediaIds, $ids);
            }
        }

        if (empty($allMediaIds)) {
            return;
        }

        // 2. Batch load all media by ID (first query)
        $allMediaIds = array_unique($allMediaIds);
        $media = InstagramMedia::whereIn('id', $allMediaIds)->get();

        $allPostIds = [];
        foreach ($media as $m) {
            self::$mediaCache[$m->id] = $m;
            $allPostIds[] = $m->instagram_post_id;
        }

        if (empty($allPostIds)) {
            return;
        }

        // 3. Batch load ALL media for related posts (second query)
        // This allows finding high-quality variants without additional queries
        $allPostIds = array_unique($allPostIds);
        $postMedia = InstagramMedia::whereIn('instagram_post_id', $allPostIds)->get();

        foreach ($postMedia as $m) {
            // Index by post_id -> type -> media_id for variant lookups
            self::$postMediaCache[$m->instagram_post_id][$m->type][$m->media_id] = $m;
            // Also cache by ID for direct lookups
            self::$mediaCache[$m->id] = $m;
        }
    }

    /**
     * Get media by ID from cache, with fallback to database query.
     */
    public static function getById(int|string $id): ?InstagramMedia
    {
        $id = (int) $id;

        // Return from cache if available
        if (isset(self::$mediaCache[$id])) {
            return self::$mediaCache[$id];
        }

        // Fallback to direct query (for single product views or cache miss)
        $media = InstagramMedia::find($id);
        if ($media) {
            self::$mediaCache[$id] = $media;
        }

        return $media;
    }

    /**
     * Get media by post ID, type, and media_id (for finding high-quality variants).
     * Returns null if not found in cache - caller should handle fallback.
     */
    public static function getByPostAndType(string $postId, string $type, ?string $mediaId): ?InstagramMedia
    {
        return self::$postMediaCache[$postId][$type][$mediaId] ?? null;
    }

    /**
     * Get all media for a specific post from cache.
     */
    public static function getMediaForPost(string $postId): array
    {
        if (!isset(self::$postMediaCache[$postId])) {
            return [];
        }

        $media = [];
        foreach (self::$postMediaCache[$postId] as $typeMedia) {
            foreach ($typeMedia as $m) {
                $media[] = $m;
            }
        }
        return $media;
    }

    /**
     * Check if preloading has been done.
     */
    public static function isPreloaded(): bool
    {
        return !empty(self::$mediaCache) || !empty(self::$postMediaCache);
    }

    /**
     * Clear the cache. Called automatically at the start of preloadForProducts().
     */
    public static function clear(): void
    {
        self::$mediaCache = [];
        self::$postMediaCache = [];
    }
}
