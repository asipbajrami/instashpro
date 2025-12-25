<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        $attributes = ProductAttribute::withCount('values')
            ->orderBy('name')
            ->get();

        return response()->json($attributes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_attributes,name',
            'slug' => 'nullable|string|max:255|unique:product_attributes,slug',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        $attribute = ProductAttribute::create($validated);

        return response()->json($attribute, 201);
    }

    public function show(ProductAttribute $attribute): JsonResponse
    {
        $attribute->loadCount('values');

        return response()->json($attribute);
    }

    public function update(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:product_attributes,name,' . $attribute->id,
            'slug' => 'sometimes|string|max:255|unique:product_attributes,slug,' . $attribute->id,
        ]);

        $attribute->update($validated);

        return response()->json($attribute);
    }

    public function destroy(ProductAttribute $attribute): JsonResponse
    {
        $attribute->delete();

        return response()->json(null, 204);
    }

    /**
     * Get all attributes with their linked structure outputs.
     */
    public function withOutputs(): JsonResponse
    {
        $attributes = ProductAttribute::with(['structureOutputs' => function ($query) {
            $query->select('id', 'key', 'type', 'description', 'parent_key', 'used_for', 'required', 'product_attribute_id');
        }])
            ->withCount(['values', 'structureOutputs'])
            ->orderBy('name')
            ->get();

        return response()->json($attributes);
    }
}
