<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCollection;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Root category IDs for each group
     */
    private const GROUP_ROOT_CATEGORIES = [
        'car' => 100,  // Cars & Vehicles
        'tech' => 1,   // Tech & Electronics
    ];

    /**
     * List all categories as a tree structure
     * Optionally filter by group (car or tech)
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->get('group');

        // Load all non-temp categories with product counts in a SINGLE query
        $allCategories = Category::withCount('products')
            ->where('is_temp', false)
            ->orderBy('score', 'desc')
            ->get()
            ->keyBy('id');

        // Determine root category IDs based on group
        if ($group && isset(self::GROUP_ROOT_CATEGORIES[$group])) {
            $rootCategoryIds = [self::GROUP_ROOT_CATEGORIES[$group]];
        } else {
            $rootCategoryIds = $allCategories->whereNull('parent_id')->pluck('id')->toArray();
        }

        // Build tree structure in PHP (much faster than recursive DB queries)
        $categories = collect($rootCategoryIds)
            ->map(fn($id) => $this->buildCategoryTree($allCategories, $id))
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Build category tree recursively from pre-loaded categories
     */
    private function buildCategoryTree($allCategories, $categoryId): ?array
    {
        $category = $allCategories->get($categoryId);
        if (!$category) {
            return null;
        }

        $children = $allCategories
            ->where('parent_id', $categoryId)
            ->map(fn($child) => $this->buildCategoryTree($allCategories, $child->id))
            ->filter()
            ->values()
            ->toArray();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'product_count' => $category->products_count,
            'children' => $children,
        ];
    }

    /**
     * Get category by slug with its products
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $category = Category::with(['children' => function ($q) {
            $q->withCount('products');
        }])
        ->withCount('products')
        ->where('slug', $slug)
        ->firstOrFail();

        $products = Product::with(['categories', 'attributeValues.attribute'])
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new CategoryResource($category),
                'products' => (new ProductCollection($products))->toArray($request),
            ],
        ]);
    }
}
