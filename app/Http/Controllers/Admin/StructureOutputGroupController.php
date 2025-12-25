<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StructureOutputGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StructureOutputGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StructureOutputGroup::withCount('structureOutputs');

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('used_for', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $groups = $query->orderBy('used_for')->get();

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'used_for' => 'required|string|unique:structure_output_groups,used_for|max:255',
            'description' => 'required|string',
        ]);

        $group = StructureOutputGroup::create($validated);

        return response()->json($group, 201);
    }

    public function show(StructureOutputGroup $structureOutputGroup): JsonResponse
    {
        return response()->json(
            $structureOutputGroup->loadCount('structureOutputs')
        );
    }

    public function update(Request $request, StructureOutputGroup $structureOutputGroup): JsonResponse
    {
        $validated = $request->validate([
            'used_for' => [
                'string',
                'max:255',
                Rule::unique('structure_output_groups')->ignore($structureOutputGroup->id),
            ],
            'description' => 'string',
        ]);

        $structureOutputGroup->update($validated);

        return response()->json($structureOutputGroup);
    }

    public function destroy(StructureOutputGroup $structureOutputGroup): JsonResponse
    {
        $structureOutputGroup->delete();

        return response()->json(null, 204);
    }
}
