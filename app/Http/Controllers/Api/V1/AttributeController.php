<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttributeResource;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttributeController extends Controller
{
    private const DEFAULT_LIMIT = 10;

    /**
     * Get all category IDs including the category and all its descendants
     */
    private function getCategoryIdsWithDescendants(Category $category): array
    {
        $ids = [$category->id];

        // Get all descendant categories recursively
        $descendants = Category::where('parent_id', $category->id)->get();
        foreach ($descendants as $child) {
            $ids = array_merge($ids, $this->getCategoryIdsWithDescendants($child));
        }

        return $ids;
    }

    /**
     * Get attribute groups for a category (including parent hierarchy)
     */
    private function getAttributeGroupsForCategory(Category $category): array
    {
        $groups = ['global']; // Global always included

        // Walk up the category tree and collect slugs
        $current = $category;
        while ($current) {
            $groups[] = $current->slug;
            $current = $current->parent;
        }

        return $groups;
    }

    /**
     * List all attributes with their values (for filter UI)
     * Returns limited values per attribute with has_more flag
     * Optional: filter by category slug to get relevant attributes
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', self::DEFAULT_LIMIT);
        $categorySlug = $request->get('category');

        // If category is provided, only show attribute values used by products in that category
        if ($categorySlug) {
            $category = Category::with('parent.parent.parent')
                ->where('slug', $categorySlug)
                ->first();

            if ($category) {
                $groups = $this->getAttributeGroupsForCategory($category);
                $categoryIds = $this->getCategoryIdsWithDescendants($category);

                // Get attribute value IDs that are used by products in this category
                $usedValueIds = DB::table('product_attribute_value_associations as pava')
                    ->join('product_category as pc', 'pava.product_id', '=', 'pc.product_id')
                    ->whereIn('pc.category_id', $categoryIds)
                    ->distinct()
                    ->pluck('pava.product_attribute_value_id');

                // Get attributes with only the values used in this category
                $attributes = ProductAttribute::whereIn('attribute_group', $groups)
                    ->get()
                    ->map(function ($attr) use ($usedValueIds, $limit) {
                        // Get values for this attribute that are used in the category
                        $values = ProductAttributeValue::where('product_attribute_id', $attr->id)
                            ->whereIn('id', $usedValueIds)
                            ->orderBy('is_temp', 'asc')
                            ->orderBy('score', 'desc')
                            ->get();

                        $totalCount = $values->count();
                        $limitedValues = $values->take($limit);

                        return [
                            'id' => $attr->id,
                            'name' => $attr->name,
                            'slug' => $attr->slug,
                            'type' => $attr->type,
                            'attribute_group' => $attr->attribute_group,
                            'values' => $limitedValues->map(fn ($v) => [
                                'id' => $v->id,
                                'value' => $v->value,
                                'is_temp' => $v->is_temp,
                            ]),
                            'total_count' => $totalCount,
                            'has_more' => $totalCount > $limit,
                        ];
                    })
                    ->filter(fn ($attr) => $attr['total_count'] > 0)
                    ->values();

                return response()->json([
                    'success' => true,
                    'data' => $attributes,
                ]);
            }
        }

        // No category filter - return all non-temp values
        $query = ProductAttribute::withCount(['values' => fn ($q) => $q->where('is_temp', false)])
            ->with(['values' => function ($q) use ($limit) {
                $q->where('is_temp', false)
                  ->orderBy('score', 'desc')
                  ->limit($limit);
            }]);

        $attributes = $query->get()
            ->filter(fn ($attr) => $attr->values_count > 0);

        return response()->json([
            'success' => true,
            'data' => $attributes->map(fn ($attr) => [
                'id' => $attr->id,
                'name' => $attr->name,
                'slug' => $attr->slug,
                'type' => $attr->type,
                'attribute_group' => $attr->attribute_group,
                'values' => $attr->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                ]),
                'total_count' => $attr->values_count,
                'has_more' => $attr->values_count > $limit,
            ])->values(),
        ]);
    }

    /**
     * Get all values for a specific attribute (for "show more")
     * Includes both permanent and temporary values
     * Optional: filter by category to only show values used by products in that category
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $attribute = ProductAttribute::findOrFail($id);
        $categorySlug = $request->get('category');

        $query = $attribute->values()
            ->orderBy('is_temp', 'asc') // Permanent first, then temporary
            ->orderBy('score', 'desc');

        // Filter by category - only show values used by products in the category
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();

            if ($category) {
                $categoryIds = $this->getCategoryIdsWithDescendants($category);

                // Get attribute value IDs used by products in this category
                $usedValueIds = DB::table('product_attribute_value_associations as pava')
                    ->join('product_category as pc', 'pava.product_id', '=', 'pc.product_id')
                    ->whereIn('pc.category_id', $categoryIds)
                    ->distinct()
                    ->pluck('pava.product_attribute_value_id');

                $query->whereIn('id', $usedValueIds);
            }
        }

        // Optional search filter
        if ($request->filled('search')) {
            $query->where('value', 'like', '%' . $request->input('search') . '%');
        }

        $values = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'type' => $attribute->type,
                'values' => $values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'is_temp' => $v->is_temp,
                ]),
                'total_count' => $values->count(),
            ],
        ]);
    }
}
