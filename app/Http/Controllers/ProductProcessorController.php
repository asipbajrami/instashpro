<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductAttributeValueAssociation;
use App\Models\ProductCategory;
use App\Models\StructureOutput;
use App\Services\EmbeddingService;
use App\Services\Llm\LlmServiceInterface;
use App\Services\Search\TypesenseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductProcessorController extends Controller
{
    private const HYBRID_SCORE_THRESHOLD = 0.60;
    private const VECTOR_DISTANCE_THRESHOLD = 0.30;
    private const PROMOTION_SCORE = 3;
    private const SEARCH_LIMIT = 5;
    private const MAX_RETRIES = 3;

    public function __construct(
        protected LlmServiceInterface $llmService,
        protected TypesenseService $typesense,
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Label posts with used_for group only (no product extraction)
     * Uses Typesense built-in embedding models to categorize posts as tech, car, or general
     */
    public function labelUsedFor(Request $request, string $username): JsonResponse
    {
        set_time_limit(300);

        $query = InstagramPost::where('username', $username);

        // Filter by unlabeled only (where used_for is null)
        // Use boolean() to properly parse 'true'/'false' strings from query params
        if ($request->boolean('unlabeled_only', true)) {
            $query->whereNull('used_for');
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $totalPosts = InstagramPost::where('username', $username)->count();
            if ($totalPosts > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "All {$totalPosts} posts for username '{$username}' have already been labeled. Use ?unlabeled_only=false to relabel."
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => "No posts found for username: {$username}"
            ]);
        }

        $results = ['labeled' => [], 'skipped' => [], 'errors' => []];

        foreach ($posts as $post) {
            try {
                $result = $this->labelPostUsedFor($post);
                $results[$result['success'] ? 'labeled' : 'skipped'][] = $result;
            } catch (Exception $e) {
                Log::error("Error labeling post {$post->id}: " . $e->getMessage());
                $results['errors'][] = [
                    'post_id' => $post->id,
                    'shortcode' => $post->shortcode,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'username' => $username,
            'summary' => [
                'total' => $posts->count(),
                'labeled' => count($results['labeled']),
                'skipped' => count($results['skipped']),
                'errors' => count($results['errors'])
            ],
            'results' => $results
        ]);
    }

    /**
     * Label a single post with its used_for group
     */
    private function labelPostUsedFor(InstagramPost $post): array
    {
        $medias = $this->getPostMedia($post);

        $validImages = [];
        foreach ($medias as $media) {
            $path = $media->media_path;
            if (Storage::disk('public')->exists($path) &&
                in_array(Storage::disk('public')->mimeType($path), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $validImages[] = [
                    'path' => $path,
                    'media_id' => $media->id
                ];
            }
        }

        // Determine structure output group using caption and first image
        $group = $this->determineStructureOutputGroup($post->caption, $validImages);

        $post->update(['used_for' => $group]);

        return [
            'success' => true,
            'post_id' => $post->id,
            'shortcode' => $post->shortcode,
            'used_for' => $group,
            'had_images' => !empty($validImages),
            'had_caption' => !empty($post->caption)
        ];
    }

    /**
     * Process posts: extract products from images/caption, create products, and label attributes
     * ALWAYS uses queue jobs for background processing (doesn't block the server)
     */
    public function process(Request $request, string $username): JsonResponse
    {
        $query = InstagramPost::where('username', $username);

        if ($request->input('unprocessed_only', true)) {
            $query->where('processed_structure', false);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $totalPosts = InstagramPost::where('username', $username)->count();
            if ($totalPosts > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "All {$totalPosts} posts for username '{$username}' have already been processed. Use ?unprocessed_only=false to reprocess."
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => "No posts found for username: {$username}"
            ]);
        }

        // Create a processing run to track progress
        $run = \App\Models\InstagramProcessingRun::create([
            'username' => $username,
            'posts_to_process' => $posts->count(),
            'posts_processed' => 0,
            'posts_skipped' => 0,
            'posts_failed' => 0,
        ]);

        // Dispatch jobs for each post (background processing)
        foreach ($posts as $post) {
            \App\Jobs\ProcessInstagramPost::dispatch($post->id, $run->id);
        }

        return response()->json([
            'success' => true,
            'username' => $username,
            'run_id' => $run->id,
            'message' => "Dispatched {$posts->count()} posts for background processing.",
            'posts_queued' => $posts->count(),
        ]);
    }

    /**
     * Public wrapper for processPost - used by queue jobs
     */
    public function processPostPublic(InstagramPost $post): array
    {
        return $this->processPost($post);
    }

    private function processPost(InstagramPost $post): array
    {
        $medias = $this->getPostMedia($post);

        if ($medias->isEmpty()) {
            return [
                'success' => false,
                'post_id' => $post->id,
                'shortcode' => $post->shortcode,
                'reason' => 'No media found'
            ];
        }

        $validImages = [];
        foreach ($medias as $media) {
            // Use public disk explicitly (Laravel 12 default local disk points to app/private)
            $path = $media->media_path;
            if (Storage::disk('public')->exists($path) &&
                in_array(Storage::disk('public')->mimeType($path), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $validImages[] = [
                    'path' => $path,
                    'media_id' => $media->id
                ];
            }
        }

        if (empty($validImages)) {
            return [
                'success' => false,
                'post_id' => $post->id,
                'shortcode' => $post->shortcode,
                'reason' => 'No valid images found'
            ];
        }

        // Determine structure output group using caption and first image
        $group = $this->determineStructureOutputGroup($post->caption, $validImages);

        // Extract products using LLM with retry logic
        $extraction = null;
        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $extraction = $this->llmService->extractProducts($validImages, $post->caption, $group);
                break;
            } catch (Exception $e) {
                $lastError = $e;
                Log::warning("LLM extraction attempt {$attempt}/" . self::MAX_RETRIES . " failed for post {$post->id}: " . $e->getMessage());
                if ($attempt < self::MAX_RETRIES) {
                    sleep(1);
                }
            }
        }

        if ($extraction === null) {
            throw $lastError ?? new Exception('Failed to extract products after ' . self::MAX_RETRIES . ' attempts');
        }

        $imageMap = $extraction['image_map'] ?? [];

        // Update post with group (tech/car) and extracted categories
        $post->update([
            'used_for' => $group,
            'llm_categories' => $this->extractAllCategories($extraction['products'] ?? []),
        ]);

        if (!$extraction['has_products'] || empty($extraction['products'])) {
            $post->update(['processed_structure' => true]);
            return [
                'success' => false,
                'post_id' => $post->id,
                'shortcode' => $post->shortcode,
                'reason' => 'No products detected',
                'group' => $group
            ];
        }

        // Clear existing associations for this post
        ProductAttributeValueAssociation::where('post_id', $post->id)->delete();

        $createdProducts = [];
        $skippedLowConfidence = 0;
        $acceptedConfidenceLevels = ['high', 'high-medium'];

        foreach ($extraction['products'] as $productData) {
            $confidence = $productData['confidence'] ?? 'low';
            if (!in_array($confidence, $acceptedConfidenceLevels)) {
                $skippedLowConfidence++;
                continue;
            }

            $product = $this->createProduct($post, $productData, $imageMap, $group, $validImages);
            if ($product) {
                $this->labelProductAttributes($post, $product, $productData, $group);
                $categories = $this->processCategories($product, $productData['categories'] ?? []);

                // Set primary category (first assigned category)
                if (!empty($categories) && $product->primary_category_id === null) {
                    $product->update(['primary_category_id' => $categories[0]->id]);
                }

                $createdProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'brand' => $productData['brand'] ?? '',
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                    'confidence' => $confidence,
                    'media_ids' => $product->instagram_media_ids
                ];
            }
        }

        $post->update(['processed_structure' => true]);

        return [
            'success' => true,
            'post_id' => $post->id,
            'shortcode' => $post->shortcode,
            'group' => $group,
            'post_type' => $extraction['post_type'] ?? null,
            'products_created' => count($createdProducts),
            'products_skipped_low_confidence' => $skippedLowConfidence,
            'products' => $createdProducts
        ];
    }

    /**
     * Determine the best structure output group for a post using Gemma 3 4B LLM
     * Uses caption text and optionally first image for classification
     */
    private function determineStructureOutputGroup(?string $caption, array $validImages): string
    {
        try {
            // Get base64 image from first valid image
            $base64Image = null;
            if (!empty($validImages)) {
                $firstImagePath = $validImages[0]['path'];
                $imageContent = Storage::disk('public')->get($firstImagePath);
                if ($imageContent) {
                    $base64Image = base64_encode($imageContent);
                }
            }

            // Classify using LLM service
            return $this->llmService->classifyPostCategory($caption, $base64Image, 'tech');
        } catch (Exception $e) {
            Log::warning("Failed to determine structure output group: " . $e->getMessage());
            return 'tech'; // Default to tech group on error
        }
    }

    private function getPostMedia(InstagramPost $post)
    {
        return InstagramMedia::whereIn('type', ['carousel_mid', 'image_mid', 'carousel_high', 'image_high'])
            ->where('instagram_post_id', $post->post_id)
            ->orderBy('media_id')
            ->get();
    }

    private function createProduct(InstagramPost $post, array $productData, array $imageMap, string $group, array $validImages): ?Product
    {
        $mediaIds = $this->findMatchingMediaIds($productData['source'] ?? [], $imageMap);
        $mediaIdArray = array_filter(explode('_', $mediaIds));

        // Get profile for denormalized columns (try ig_id first, then username)
        $profile = $post->ig_id
            ? InstagramProfile::where('ig_id', $post->ig_id)->first()
            : InstagramProfile::where('username', $post->username)->first();

        // Get image URLs - prioritize local storage (more reliable than CDN)
        $primaryImageUrl = null;
        $thumbnailUrl = null;
        if (!empty($mediaIdArray)) {
            $firstMedia = InstagramMedia::find((int) $mediaIdArray[0]);
            if ($firstMedia) {
                // Use local storage URL if media_path exists
                if ($firstMedia->media_path && Storage::disk('public')->exists($firstMedia->media_path)) {
                    $primaryImageUrl = Storage::disk('public')->url($firstMedia->media_path);
                    $thumbnailUrl = $primaryImageUrl; // Use same URL for thumbnail
                } else {
                    // Fallback to CDN URLs
                    $primaryImageUrl = $firstMedia->display_url ?? $post->display_url;
                    $thumbnailUrl = $firstMedia->thumbnail_url ?? $post->thumbnail_url;
                }
            }
        }
        // Final fallback to post's CDN URLs
        if (!$primaryImageUrl) {
            $primaryImageUrl = $post->display_url;
            $thumbnailUrl = $post->thumbnail_url;
        }

        $price = $productData['price'] ?? 0;
        $discountPrice = $productData['discount_price'] ?? 0;

        return Product::create([
            'name' => trim(($productData['brand'] ?? '') . ' ' . ($productData['name'] ?? '')),
            'type' => $productData['type'] ?? 'general_product',
            'price' => $price,
            'discount_price' => $discountPrice,
            'currency' => $productData['currency'] ?? 'ALL',
            'description' => $productData['product_details'] ?? '',
            'instagram_media_ids' => $mediaIds,
            // Denormalized columns
            'group' => $group,
            'instagram_profile_id' => $profile?->id,
            'seller_username' => $profile?->username,
            'instagram_post_id' => $post->post_id,
            'published_at' => $post->published_at,
            'media_count' => max(1, count($mediaIdArray)),
            'has_discount' => $discountPrice > 0 && $discountPrice < $price,
            'primary_image_url' => $primaryImageUrl,
            'thumbnail_url' => $thumbnailUrl,
        ]);
    }

    private function findMatchingMediaIds(array $sources, array $imageMap): string
    {
        if (empty($sources) || empty($imageMap)) {
            return (string)(reset($imageMap) ?: '');
        }

        $ids = [];
        foreach ($sources as $source) {
            if (isset($imageMap[$source])) {
                $ids[] = $imageMap[$source];
            }
        }

        return implode('_', array_unique($ids));
    }

    private function labelProductAttributes(InstagramPost $post, Product $product, array $productData, string $group): void
    {
        $topLevelMap = [
            'brand' => 'brand',
            'condition' => 'condition',
            'currency' => 'currency',
        ];

        foreach ($topLevelMap as $dataKey => $structureKey) {
            $value = $productData[$dataKey] ?? null;
            $this->processAttributeByKey($post, $product, $structureKey, $value, $group);
        }

        $attributes = $productData['attributes'] ?? [];
        foreach ($attributes as $key => $value) {
            $this->processAttributeByKey($post, $product, $key, $value, $group);
        }
    }

    private function processAttributeByKey(
        InstagramPost $post,
        Product $product,
        string $key,
        mixed $value,
        string $group
    ): void {
        if (!$this->isValidAttributeValue($key, $value)) {
            return;
        }

        $value = trim((string) $value);

        // Look up structure output using parent_key (tech/car group)
        // This finds attributes across all product types in the group
        $structureOutput = StructureOutput::where('key', $key)
            ->where('parent_key', $group)
            ->whereNotNull('product_attribute_id')
            ->first();

        if (!$structureOutput?->product_attribute_id) {
            return;
        }

        $existingAttribute = ProductAttributeValue::where('product_attribute_id', $structureOutput->product_attribute_id)
            ->whereRaw('LOWER(TRIM(value)) = ?', [strtolower($value)])
            ->first();

        if ($existingAttribute) {
            $this->createAssociation($existingAttribute->id, $post->id, $product->id, $existingAttribute->is_temp);
            $existingAttribute->increment('score', 1);

            // Auto-promote if score exceeds threshold
            if ($existingAttribute->is_temp && $existingAttribute->score > self::PROMOTION_SCORE) {
                $existingAttribute->update(['is_temp' => false]);
            }
        } else {
            $newAttribute = ProductAttributeValue::create([
                'value' => $value,
                'ai_value' => $value,
                'type_value' => 'select',
                'is_temp' => true,
                'score' => 1,
                'product_attribute_id' => $structureOutput->product_attribute_id
            ]);

            $this->createAssociation($newAttribute->id, $post->id, $product->id, true);
        }
    }

    private function isValidAttributeValue(string $key, mixed $value): bool
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return false;
        }

        // Filter out placeholder/unknown values
        $invalidValues = [
            'unknown', 'n/a', 'na', 'none', 'not available', 'not specified',
            'unspecified', 'null', 'undefined', '-', '--', '---'
        ];

        if (is_string($value) && in_array(strtolower(trim($value)), $invalidValues)) {
            return false;
        }

        if ($key !== 'currency' && is_string($value) && strlen(trim($value)) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Create product-attribute association
     * FIXED: Uses correct column name 'product_attribute_value_id'
     */
    private function createAssociation(int $attributeValueId, int $postId, int $productId, bool $isTemp): void
    {
        ProductAttributeValueAssociation::updateOrCreate(
            [
                'product_attribute_value_id' => $attributeValueId, // FIXED column name
                'post_id' => $postId,
                'product_id' => $productId,
            ],
            ['is_temp' => $isTemp]
        );
    }

    private function extractAllCategories(array $products): array
    {
        $categories = [];
        foreach ($products as $product) {
            if (!empty($product['categories'])) {
                $categories = array_merge($categories, $product['categories']);
            }
        }
        return array_unique($categories);
    }

    private function processCategories(Product $product, array $categories): array
    {
        $assignedCategories = [];

        foreach ($categories as $categoryName) {
            $categoryName = trim($categoryName);
            if (empty($categoryName)) {
                continue;
            }

            $category = $this->findCategoryByName($categoryName);
            if ($category) {
                ProductCategory::updateOrCreate(
                    ['product_id' => $product->id, 'category_id' => $category->id],
                    ['similarity_score' => null, 'is_temp' => false]
                );
                $category->increment('score');
                $assignedCategories[] = $category;
            }
        }

        return $assignedCategories;
    }

    /**
     * Find category by name using Typesense semantic search
     * Uses multilingual-e5-large embedding for semantic similarity
     * Falls back to database queries if Typesense fails
     */
    private function findCategoryByName(string $name): ?Category
    {
        $lowerName = strtolower(trim($name));
        if (empty($lowerName)) {
            return null;
        }

        // Try Typesense semantic search first
        try {
            $results = $this->typesense->searchCategories($lowerName, 1);
            if (!empty($results['hits'])) {
                $hit = $results['hits'][0];
                $vectorDistance = $hit['vector_distance'] ?? 1;

                // Accept match if distance is low enough (good semantic match)
                if ($vectorDistance <= self::VECTOR_DISTANCE_THRESHOLD) {
                    $categoryId = $hit['document']['id'];
                    $category = Category::find($categoryId);
                    if ($category) {
                        return $category;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("Typesense category search failed for '{$name}': " . $e->getMessage());
        }

        // Fallback to database queries
        $slug = str_replace(' ', '-', $lowerName);

        // 1. Exact name match (case-insensitive)
        $category = Category::whereRaw('LOWER(name) = ?', [$lowerName])->first();
        if ($category) return $category;

        // 2. Slug match
        $category = Category::where('slug', $slug)->first();
        if ($category) return $category;

        // 3. Singular/Plural match (add 's' or remove 's')
        $pluralName = $lowerName . 's';
        $singularName = rtrim($lowerName, 's');

        $category = Category::whereRaw('LOWER(name) = ? OR LOWER(name) = ?', [$pluralName, $singularName])->first();
        if ($category) return $category;

        return null;
    }

    private function findBestCategoryMatch(array $hits, string $searchValue): ?array
    {
        $normalizedSearch = strtolower(trim($searchValue));

        foreach ($hits as $hit) {
            $docValue = $hit['document']['name'] ?? '';

            if (strtolower(trim($docValue)) === $normalizedSearch) {
                return $hit;
            }

            $hybridScore = $hit['hybrid_search_info']['rank_fusion_score']
                ?? $hit['text_match_info']['score']
                ?? 0;
            $vectorDistance = $hit['vector_distance'] ?? 1;

            if ($hybridScore >= self::HYBRID_SCORE_THRESHOLD && $vectorDistance <= self::VECTOR_DISTANCE_THRESHOLD) {
                return $hit;
            }
        }
        return null;
    }

    private function linkExistingCategory(Product $product, array $hit): void
    {
        $categoryId = $hit['document']['id'];
        $vectorDistance = $hit['vector_distance'] ?? null;
        $isTemp = $hit['document']['is_temp'] ?? false;

        $category = Category::find($categoryId);
        if (!$category) {
            return;
        }

        ProductCategory::updateOrCreate(
            ['product_id' => $product->id, 'category_id' => $categoryId],
            ['similarity_score' => $vectorDistance, 'is_temp' => $isTemp]
        );

        $category->increment('score');

        if ($isTemp && $category->score > self::PROMOTION_SCORE) {
            $category->update(['is_temp' => false]);
        }
    }
}
