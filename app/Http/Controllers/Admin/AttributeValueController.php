<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttributeValueController extends Controller
{
    public function index(ProductAttribute $attribute): JsonResponse
    {
        $values = $attribute->values()
            ->orderBy('score', 'desc')
            ->orderBy('value')
            ->get();

        return response()->json($values);
    }

    public function store(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'ai_value' => 'nullable|string|max:255',
            'type_value' => 'nullable|string|max:50',
            'is_temp' => 'boolean',
            'score' => 'integer|min:0|max:100',
        ]);

        $value = $attribute->values()->create([
            'value' => $validated['value'],
            'ai_value' => $validated['ai_value'] ?? strtolower($validated['value']),
            'type_value' => $validated['type_value'] ?? 'select',
            'is_temp' => $validated['is_temp'] ?? false,
            'score' => $validated['score'] ?? 10,
        ]);

        return response()->json($value, 201);
    }

    public function update(Request $request, ProductAttribute $attribute, ProductAttributeValue $value): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'sometimes|string|max:255',
            'ai_value' => 'nullable|string|max:255',
            'type_value' => 'nullable|string|max:50',
            'is_temp' => 'boolean',
            'score' => 'integer|min:0|max:100',
        ]);

        $value->update($validated);

        return response()->json($value);
    }

    public function destroy(ProductAttribute $attribute, ProductAttributeValue $value): JsonResponse
    {
        $value->delete();

        return response()->json(null, 204);
    }
}
