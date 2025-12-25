<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\InstagramMedia;
use App\Models\InstagramProfile;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Services\EmbeddingService;
use App\Services\Search\TypesenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected TypesenseService $typesenseService
    ) {}

    /**
     * List products with filtering, pagination, and facets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['categories', 'attributeValues.attribute']);

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $perPage = min($request->get('per_page', 24), 100);
        $products = $query->paginate($perPage);

        $facets = $this->computeFacets($request);

        return response()->json([
            'success' => true,
            'data' => (new ProductCollection($products))->withFacets($facets)->toArray($request),
        ]);
    }

    /**
     * Get single product by ID
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['categories', 'attributeValues.attribute'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Search products (text search)
     * Searches across: name, description, type, currency, seller, and attribute values
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        $searchTerm = "%{$query}%";
        $group = $request->get('group');

        $productsQuery = Product::query()
            ->with(['categories', 'attributeValues.attribute'])
            ->where(function ($q) use ($searchTerm) {
                // Search in product columns
                $q->where('name', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm)
                  ->orWhere('type', 'LIKE', $searchTerm)
                  ->orWhere('currency', 'LIKE', $searchTerm)
                  ->orWhere('seller_username', 'LIKE', $searchTerm)
                  // Search in attribute values
                  ->orWhereHas('attributeValues', function ($attrQuery) use ($searchTerm) {
                      $attrQuery->where('value', 'LIKE', $searchTerm);
                  });
            });

        // Filter by group if provided
        if ($group) {
            $productsQuery->where('group', $group);
        }

        $products = $productsQuery->paginate($request->get('per_page', 24));

        $facets = $this->computeFacets($request);

        return response()->json([
            'success' => true,
            'data' => (new ProductCollection($products))->withFacets($facets)->toArray($request),
        ]);
    }

    /**
     * Advanced search with specific field targeting
     * Allows searching specific columns and attributes
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $group = $request->get('group');
        $perPage = min($request->get('per_page', 24), 100);

        $productsQuery = Product::query()
            ->with(['categories', 'attributeValues.attribute']);

        // Filter by group
        if ($group) {
            $productsQuery->where('group', $group);
        }

        // General search term (searches all fields)
        if ($q = $request->get('q')) {
            $searchTerm = "%{$q}%";
            $productsQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', $searchTerm)
                      ->orWhere('description', 'LIKE', $searchTerm)
                      ->orWhere('type', 'LIKE', $searchTerm)
                      ->orWhere('currency', 'LIKE', $searchTerm)
                      ->orWhere('seller_username', 'LIKE', $searchTerm)
                      ->orWhereHas('attributeValues', function ($attrQuery) use ($searchTerm) {
                          $attrQuery->where('value', 'LIKE', $searchTerm);
                      });
            });
        }

        // Specific field searches
        if ($name = $request->get('name')) {
            $productsQuery->where('name', 'LIKE', "%{$name}%");
        }

        if ($description = $request->get('description')) {
            $productsQuery->where('description', 'LIKE', "%{$description}%");
        }

        if ($type = $request->get('type')) {
            $productsQuery->where('type', $type);
        }

        if ($seller = $request->get('seller')) {
            $productsQuery->where('seller_username', 'LIKE', "%{$seller}%");
        }

        // Price range
        if ($minPrice = $request->get('min_price')) {
            $productsQuery->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->get('max_price')) {
            $productsQuery->where('price', '<=', $maxPrice);
        }

        // Category filter
        if ($categoryId = $request->get('category_id')) {
            $categoryIds = $this->getCategoryWithDescendants((int) $categoryId);
            $productsQuery->where(function ($q) use ($categoryIds) {
                $q->whereIn('primary_category_id', $categoryIds)
                  ->orWhereHas('categories', function ($catQ) use ($categoryIds) {
                      $catQ->whereIn('categories.id', $categoryIds);
                  });
            });
        }

        // Attribute filters - format: attr[Brand]=Apple,Samsung
        $attrFilters = $request->get('attr', []);
        if (!empty($attrFilters) && is_array($attrFilters)) {
            foreach ($attrFilters as $attrName => $values) {
                if (!is_array($values)) {
                    $values = explode(',', $values);
                }
                $values = array_filter($values);

                if (!empty($values)) {
                    $productsQuery->whereHas('attributeValues', function ($q) use ($attrName, $values) {
                        $q->whereHas('attribute', function ($attrQuery) use ($attrName) {
                            $attrQuery->where('name', $attrName);
                        })->whereIn('value', $values);
                    });
                }
            }
        }

        // Attribute value search (search across any attribute)
        if ($attrValue = $request->get('attr_value')) {
            $productsQuery->whereHas('attributeValues', function ($q) use ($attrValue) {
                $q->where('value', 'LIKE', "%{$attrValue}%");
            });
        }

        // Has price filter
        if ($request->boolean('has_price')) {
            $productsQuery->where('price', '>', 0);
        }

        // Sorting
        $this->applySorting($productsQuery, $request);

        $products = $productsQuery->paginate($perPage);
        $facets = $this->computeFacets($request);

        return response()->json([
            'success' => true,
            'data' => (new ProductCollection($products))->withFacets($facets)->toArray($request),
        ]);
    }

    /**
     * Image/semantic search using CLIP embeddings via Typesense
     */
    public function imageSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min($request->get('limit', 24), 100);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        try {
            $embedding = $this->embeddingService->getClipTextEmbedding($query);

            if (!$embedding) {
                return $this->search($request);
            }

            $searchParameters = [
                'searches' => [
                    [
                        'collection' => 'instagram_media',
                        'q' => '*',
                        'limit' => $limit * 2,
                        'exclude_fields' => 'embedding_clip',
                        'vector_query' => "embedding_clip:([" . implode(',', $embedding) . "], k:" . ($limit * 2) . ")",
                    ]
                ]
            ];

            $searchResults = $this->typesenseService->client->multiSearch->perform($searchParameters);

            $postIds = [];
            $scores = [];
            if (!empty($searchResults['results'][0]['hits'])) {
                foreach ($searchResults['results'][0]['hits'] as $hit) {
                    $postId = $hit['document']['instagram_post_id'] ?? null;
                    if ($postId && !isset($scores[$postId])) {
                        $postIds[] = $postId;
                        $scores[$postId] = $hit['vector_distance'] ?? 1;
                    }
                }
            }

            if (empty($postIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => [],
                        'pagination' => ['current_page' => 1, 'per_page' => $limit, 'total' => 0, 'total_pages' => 0],
                        'facets' => $this->computeFacets($request),
                    ],
                ]);
            }

            $mediaRecords = InstagramMedia::whereIn('instagram_post_id', $postIds)
                ->pluck('id')
                ->toArray();

            $products = Product::query()
                ->with(['categories', 'attributeValues.attribute'])
                ->where(function ($q) use ($mediaRecords) {
                    foreach ($mediaRecords as $mediaId) {
                        $q->orWhere('instagram_media_ids', 'LIKE', "%{$mediaId}%");
                    }
                })
                ->limit($limit)
                ->get();

            $products = $products->sortBy(function ($product) use ($scores) {
                $mediaIds = explode('_', $product->instagram_media_ids ?? '');
                $firstMedia = InstagramMedia::find($mediaIds[0] ?? 0);
                $postId = $firstMedia?->instagram_post_id;
                return $scores[$postId] ?? 999;
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => ProductResource::collection($products),
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $limit,
                        'total' => $products->count(),
                        'total_pages' => 1,
                    ],
                    'facets' => $this->computeFacets($request),
                    'search_type' => 'image_similarity',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search by uploaded image using CLIP embeddings
     */
    public function imageUploadSearch(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        $base64Image = $request->input('image');
        $limit = min($request->get('limit', 24), 100);

        if (str_contains($base64Image, ',')) {
            $base64Image = explode(',', $base64Image)[1];
        }

        try {
            $embedding = $this->embeddingService->getImageEmbedding($base64Image);

            if (!$embedding) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process image',
                ], 400);
            }

            $searchParameters = [
                'searches' => [
                    [
                        'collection' => 'instagram_media',
                        'q' => '*',
                        'limit' => $limit * 2,
                        'exclude_fields' => 'embedding_clip',
                        'vector_query' => "embedding_clip:([" . implode(',', $embedding) . "], k:" . ($limit * 2) . ")",
                    ]
                ]
            ];

            $searchResults = $this->typesenseService->client->multiSearch->perform($searchParameters);

            $postIds = [];
            $scores = [];
            if (!empty($searchResults['results'][0]['hits'])) {
                foreach ($searchResults['results'][0]['hits'] as $hit) {
                    $postId = $hit['document']['instagram_post_id'] ?? null;
                    if ($postId && !isset($scores[$postId])) {
                        $postIds[] = $postId;
                        $scores[$postId] = $hit['vector_distance'] ?? 1;
                    }
                }
            }

            if (empty($postIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => [],
                        'pagination' => ['current_page' => 1, 'per_page' => $limit, 'total' => 0, 'total_pages' => 0],
                    ],
                ]);
            }

            $mediaRecords = InstagramMedia::whereIn('instagram_post_id', $postIds)
                ->pluck('id')
                ->toArray();

            $products = Product::query()
                ->with(['categories', 'attributeValues.attribute'])
                ->where(function ($q) use ($mediaRecords) {
                    foreach ($mediaRecords as $mediaId) {
                        $q->orWhere('instagram_media_ids', 'LIKE', "%{$mediaId}%");
                    }
                })
                ->limit($limit)
                ->get();

            $products = $products->sortBy(function ($product) use ($scores) {
                $mediaIds = explode('_', $product->instagram_media_ids ?? '');
                $firstMedia = InstagramMedia::find($mediaIds[0] ?? 0);
                $postId = $firstMedia?->instagram_post_id;
                return $scores[$postId] ?? 999;
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => ProductResource::collection($products),
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $limit,
                        'total' => $products->count(),
                        'total_pages' => 1,
                    ],
                    'search_type' => 'image_upload',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all descendant category IDs for a given category
     */
    protected function getCategoryWithDescendants(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = Category::where('parent_id', $categoryId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getCategoryWithDescendants($childId));
        }

        return $ids;
    }

    /**
     * Determine which group (tech or car) a category belongs to based on its root
     */
    protected function getGroupFromCategoryId(int $categoryId): ?string
    {
        // Get the category and traverse up to find root
        $category = Category::find($categoryId);
        if (!$category) {
            return null;
        }

        // Traverse up to root
        while ($category->parent_id) {
            $category = Category::find($category->parent_id);
            if (!$category) {
                return null;
            }
        }

        // Map root category ID to group
        $rootToGroup = [
            1 => 'tech',   // Tech & Electronics
            100 => 'car',  // Cars & Vehicles
        ];

        return $rootToGroup[$category->id] ?? null;
    }

    /**
     * Get relevant attribute groups for a given group (tech or car)
     */
    protected function getRelevantAttributeGroups(?string $group): array
    {
        if (!$group) {
            return []; // No filter - show all
        }

        $groupAttributes = [
            'tech' => ['global', 'tech-electronics', 'monitors', 'headphones'],
            'car' => ['global', 'cars-vehicles', 'cars', 'motorcycles'],
        ];

        return $groupAttributes[$group] ?? [];
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, Request $request): void
    {
        // Filter by group (car or tech) - uses denormalized column
        if ($group = $request->get('group')) {
            $query->where('group', $group);
        }

        if ($categoryId = $request->get('category_id')) {
            // Get all descendant categories (children, grandchildren, etc.)
            $categoryIds = $this->getCategoryWithDescendants((int) $categoryId);

            // Use primary_category_id for faster lookup, fallback to whereHas for multi-category
            $query->where(function ($q) use ($categoryIds) {
                $q->whereIn('primary_category_id', $categoryIds)
                  ->orWhereHas('categories', function ($catQ) use ($categoryIds) {
                      $catQ->whereIn('categories.id', $categoryIds);
                  });
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($priceMin = $request->get('price_min')) {
            $query->where('price', '>=', $priceMin);
        }
        if ($priceMax = $request->get('price_max')) {
            $query->where('price', '<=', $priceMax);
        }

        if ($filters = $request->get('filter')) {
            foreach ($filters as $attributeName => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }

                $query->whereHas('attributeValues', function ($q) use ($attributeName, $values) {
                    $q->whereHas('attribute', function ($attrQuery) use ($attributeName) {
                        $attrQuery->where('name', 'LIKE', $attributeName);
                    })->whereIn('value', $values);
                });
            }
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by Instagram profile - uses denormalized seller_username column
        if ($profileFilter = $request->get('profile')) {
            // Try direct match on seller_username first (fast)
            $query->where('seller_username', $profileFilter);
        }

        // Filter by currency
        if ($currency = $request->get('currency')) {
            $query->where('currency', $currency);
        }

        // Filter by has_discount
        if ($request->boolean('has_discount')) {
            $query->where('has_discount', true);
        }

        // Filter to show only products with price
        if ($request->boolean('has_price')) {
            $query->where('price', '>', 0);
        }
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, Request $request): void
    {
        $sort = $request->get('sort', 'newest');

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'oldest':
                // Use published_at (Instagram post date), fallback to created_at
                $query->orderByRaw('COALESCE(published_at, created_at) ASC');
                break;
            case 'newest':
            default:
                // Use published_at (Instagram post date), fallback to created_at
                $query->orderByRaw('COALESCE(published_at, created_at) DESC');
                break;
        }
    }

    /**
     * Get root category ID for a group
     */
    protected function getRootCategoryForGroup(string $group): ?int
    {
        $groupRoots = [
            'car' => 100,  // Cars & Vehicles
            'tech' => 1,   // Tech & Electronics
        ];

        return $groupRoots[$group] ?? null;
    }

    /**
     * Compute facets for filtering UI
     * Filters by group if provided
     */
    protected function computeFacets(Request $request): array
    {
        $group = $request->get('group');

        // Get categories filtered by group
        $categoriesQuery = Category::withCount('products')
            ->where('is_temp', false)
            ->orderBy('products_count', 'desc');

        if ($group && $rootCategoryId = $this->getRootCategoryForGroup($group)) {
            // Get the root category and its children
            $categoriesQuery->where(function ($q) use ($rootCategoryId) {
                $q->where('id', $rootCategoryId)
                  ->orWhere('parent_id', $rootCategoryId);
            });
        } else {
            $categoriesQuery->whereNull('parent_id');
        }

        $categories = $categoriesQuery
            ->limit(20)
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->products_count,
            ]);

        // Get attributes based on products in the selected category/group
        $categoryIdFromRequest = $request->get('category_id');

        // Determine which attribute groups are relevant
        $relevantGroups = [];
        if ($categoryIdFromRequest) {
            $categoryGroup = $this->getGroupFromCategoryId((int) $categoryIdFromRequest);
            $relevantGroups = $this->getRelevantAttributeGroups($categoryGroup);
        } elseif ($group) {
            $relevantGroups = $this->getRelevantAttributeGroups($group);
        }

        if ($categoryIdFromRequest) {
            // Get all category IDs (including descendants)
            $categoryIds = $this->getCategoryWithDescendants((int) $categoryIdFromRequest);

            // Get attribute values that actually exist in products of this category with their counts
            $attributeValuesQuery = DB::table('product_attribute_values')
                ->select([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->selectRaw("COUNT(DISTINCT product_attribute_value_associations.product_id) as product_count")
                ->join('product_attribute_value_associations', 'product_attribute_values.id', '=', 'product_attribute_value_associations.product_attribute_value_id')
                ->join('product_category', 'product_attribute_value_associations.product_id', '=', 'product_category.product_id')
                ->join('product_attributes', 'product_attribute_values.product_attribute_id', '=', 'product_attributes.id')
                ->whereIn('product_category.category_id', $categoryIds)
                ->where('product_attribute_values.is_temp', false);

            // Filter by relevant attribute groups if we have a category group
            if (!empty($relevantGroups)) {
                $attributeValuesQuery->whereIn('product_attributes.attribute_group', $relevantGroups);
            }

            $attributeValues = $attributeValuesQuery
                ->groupBy([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->having('product_count', '>', 0)
                ->orderBy('product_attribute_values.score', 'desc')
                ->get();

            // Group values by attribute and get unique attribute IDs
            $attributeIds = $attributeValues->pluck('product_attribute_id')->unique()->toArray();

            // Get the attributes (already filtered by group via the join)
            $attributesData = ProductAttribute::whereIn('id', $attributeIds)->get()->keyBy('id');

            // Build the attributes array with filtered values
            $attributes = collect($attributeIds)->map(function ($attrId) use ($attributesData, $attributeValues) {
                $attr = $attributesData[$attrId] ?? null;
                if (!$attr) return null;

                $values = $attributeValues
                    ->where('product_attribute_id', $attrId)
                    ->take(20)
                    ->map(fn ($val) => [
                        'id' => $val->id,
                        'value' => $val->value,
                        'count' => $val->product_count,
                    ])
                    ->values();

                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'group' => $attr->attribute_group,
                    'values' => $values,
                ];
            })->filter()->values();
        } elseif ($group) {
            // Filter by group - uses denormalized group column
            // Get attribute values that actually exist in products of this group with their counts
            $attributeValuesQuery = DB::table('product_attribute_values')
                ->select([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->selectRaw("COUNT(DISTINCT product_attribute_value_associations.product_id) as product_count")
                ->join('product_attribute_value_associations', 'product_attribute_values.id', '=', 'product_attribute_value_associations.product_attribute_value_id')
                ->join('products', 'product_attribute_value_associations.product_id', '=', 'products.id')
                ->join('product_attributes', 'product_attribute_values.product_attribute_id', '=', 'product_attributes.id')
                ->where('products.group', $group)
                ->where('product_attribute_values.is_temp', false);

            // Filter by relevant attribute groups
            if (!empty($relevantGroups)) {
                $attributeValuesQuery->whereIn('product_attributes.attribute_group', $relevantGroups);
            }

            $attributeValues = $attributeValuesQuery
                ->groupBy([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->having('product_count', '>', 0)
                ->orderBy('product_attribute_values.score', 'desc')
                ->get();

            // Group values by attribute and get unique attribute IDs
            $attributeIds = $attributeValues->pluck('product_attribute_id')->unique()->toArray();

            // Get the attributes
            $attributesData = ProductAttribute::whereIn('id', $attributeIds)->get()->keyBy('id');

            // Build the attributes array with filtered values
            $attributes = collect($attributeIds)->map(function ($attrId) use ($attributesData, $attributeValues) {
                $attr = $attributesData[$attrId] ?? null;
                if (!$attr) return null;

                $values = $attributeValues
                    ->where('product_attribute_id', $attrId)
                    ->take(20)
                    ->map(fn ($val) => [
                        'id' => $val->id,
                        'value' => $val->value,
                        'count' => $val->product_count,
                    ])
                    ->values();

                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'group' => $attr->attribute_group,
                    'values' => $values,
                ];
            })->filter()->values();
        } else {
            // No filter - get all attributes with product counts
            $attributeValues = DB::table('product_attribute_values')
                ->select([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->selectRaw("COUNT(DISTINCT product_attribute_value_associations.product_id) as product_count")
                ->join('product_attribute_value_associations', 'product_attribute_values.id', '=', 'product_attribute_value_associations.product_attribute_value_id')
                ->where('product_attribute_values.is_temp', false)
                ->groupBy([
                    'product_attribute_values.id',
                    'product_attribute_values.product_attribute_id',
                    'product_attribute_values.value',
                    'product_attribute_values.score',
                ])
                ->having('product_count', '>', 0)
                ->orderBy('product_attribute_values.score', 'desc')
                ->get();

            // Group values by attribute and get unique attribute IDs
            $attributeIds = $attributeValues->pluck('product_attribute_id')->unique()->toArray();

            // Get the attributes
            $attributesData = ProductAttribute::whereIn('id', $attributeIds)->get()->keyBy('id');

            // Build the attributes array with filtered values
            $attributes = collect($attributeIds)->map(function ($attrId) use ($attributesData, $attributeValues) {
                $attr = $attributesData[$attrId] ?? null;
                if (!$attr) return null;

                $values = $attributeValues
                    ->where('product_attribute_id', $attrId)
                    ->take(20)
                    ->map(fn ($val) => [
                        'id' => $val->id,
                        'value' => $val->value,
                        'count' => $val->product_count,
                    ])
                    ->values();

                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'group' => $attr->attribute_group,
                    'values' => $values,
                ];
            })->filter()->values();
        }

        // Get price range filtered by group - uses denormalized column
        $priceQuery = Product::query();
        if ($group) {
            $priceQuery->where('group', $group);
        }
        $priceRange = $priceQuery
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        // Get types filtered by group - uses denormalized column
        $typesQuery = Product::selectRaw('type, COUNT(*) as count')
            ->whereNotNull('type');

        if ($group) {
            $typesQuery->where('group', $group);
        }

        $types = $typesQuery
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->type,
                'label' => ucwords(str_replace('_', ' ', $item->type)),
                'count' => $item->count,
            ]);

        // Get Instagram profiles with product counts - uses denormalized columns
        $profiles = $this->getProfilesWithProductCounts($group);

        // Get currencies with product counts - uses denormalized column
        $currenciesQuery = Product::selectRaw('currency, COUNT(*) as count')
            ->whereNotNull('currency');

        if ($group) {
            $currenciesQuery->where('group', $group);
        }

        $currencies = $currenciesQuery
            ->groupBy('currency')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->currency,
                'label' => $item->currency,
                'count' => $item->count,
            ]);

        return [
            'categories' => $categories,
            'attributes' => $attributes,
            'types' => $types,
            'profiles' => $profiles,
            'currencies' => $currencies,
            'price_range' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
        ];
    }

    /**
     * Get Instagram profiles that have products, with product counts
     * Uses denormalized instagram_profile_id and seller_username for fast queries
     */
    protected function getProfilesWithProductCounts(?string $group = null): array
    {
        // Simple GROUP BY on denormalized seller_username column
        $query = Product::selectRaw('instagram_profile_id, seller_username, COUNT(*) as product_count')
            ->whereNotNull('instagram_profile_id')
            ->whereNotNull('seller_username');

        if ($group) {
            $query->where('group', $group);
        }

        $profileCounts = $query
            ->groupBy('instagram_profile_id', 'seller_username')
            ->orderBy('product_count', 'desc')
            ->limit(20)
            ->get();

        if ($profileCounts->isEmpty()) {
            return [];
        }

        // Get profile details
        $profileIds = $profileCounts->pluck('instagram_profile_id')->toArray();
        $profileDetails = InstagramProfile::whereIn('id', $profileIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        return $profileCounts
            ->map(function ($item) use ($profileDetails) {
                $profile = $profileDetails[$item->instagram_profile_id] ?? null;
                if (!$profile) {
                    return null;
                }

                return [
                    'id' => $profile->id,
                    'username' => $profile->username,
                    'full_name' => $profile->full_name,
                    'profile_pic_url' => $profile->profile_pic_url,
                    'is_verified' => $profile->is_verified,
                    'product_count' => $item->product_count,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
