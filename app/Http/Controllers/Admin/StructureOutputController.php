<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\StructureOutput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StructureOutputController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StructureOutput::with('productAttribute');

        if ($request->has('parent_key')) {
            $query->where('parent_key', $request->parent_key);
        }

        if ($request->has('used_for')) {
            $query->where('used_for', $request->used_for);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('key', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $outputs = $query->orderBy('parent_key')
            ->orderBy('used_for')
            ->orderBy('key')
            ->paginate($request->get('per_page', 50));

        return response()->json($outputs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'type' => 'required|string|in:string,number,boolean',
            'description' => 'required|string',
            'parent_key' => 'required|string|in:tech,car',
            'used_for' => 'required|string',
            'required' => 'boolean',
            'enum_values' => 'nullable|string',
            'product_attribute_id' => 'nullable|exists:product_attributes,id',
        ]);

        $exists = StructureOutput::where('key', $validated['key'])
            ->where('used_for', $validated['used_for'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A structure output with this key already exists for this product type.',
            ], 422);
        }

        $output = StructureOutput::create($validated);

        return response()->json($output->load('productAttribute'), 201);
    }

    public function show(StructureOutput $structureOutput): JsonResponse
    {
        return response()->json($structureOutput->load('productAttribute'));
    }

    public function update(Request $request, StructureOutput $structureOutput): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'string|max:255',
            'type' => 'string|in:string,number,boolean',
            'description' => 'string',
            'parent_key' => 'string|in:tech,car',
            'used_for' => 'string',
            'required' => 'boolean',
            'enum_values' => 'nullable|string',
            'product_attribute_id' => 'nullable|exists:product_attributes,id',
        ]);

        if (isset($validated['key']) || isset($validated['used_for'])) {
            $key = $validated['key'] ?? $structureOutput->key;
            $usedFor = $validated['used_for'] ?? $structureOutput->used_for;

            $exists = StructureOutput::where('key', $key)
                ->where('used_for', $usedFor)
                ->where('id', '!=', $structureOutput->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'A structure output with this key already exists for this product type.',
                ], 422);
            }
        }

        $structureOutput->update($validated);

        return response()->json($structureOutput->load('productAttribute'));
    }

    public function destroy(StructureOutput $structureOutput): JsonResponse
    {
        $structureOutput->delete();

        return response()->json(null, 204);
    }

    public function productAttributes(): JsonResponse
    {
        $attributes = ProductAttribute::orderBy('name')->get(['id', 'name']);

        return response()->json($attributes);
    }
}
