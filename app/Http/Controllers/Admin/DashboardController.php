<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramProfile;
use App\Models\StructureOutput;
use App\Models\StructureOutputGroup;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $now = now();

        return response()->json([
            'profiles' => [
                'total' => InstagramProfile::count(),
                'active' => InstagramProfile::where('status', 'active')->count(),
                'inactive' => InstagramProfile::where('status', 'inactive')->count(),
                'suspended' => InstagramProfile::where('status', 'suspended')->count(),
                'due_for_scrape' => InstagramProfile::where('status', 'active')
                    ->where(function ($query) use ($now) {
                        $query->whereNull('next_scrape_at')
                            ->orWhere('next_scrape_at', '<=', $now);
                    })->count(),
            ],
            'structure_outputs' => [
                'total' => StructureOutput::count(),
                'by_group' => StructureOutput::selectRaw('parent_key, COUNT(*) as count')
                    ->groupBy('parent_key')
                    ->pluck('count', 'parent_key'),
                'by_used_for' => StructureOutput::selectRaw('used_for, COUNT(*) as count')
                    ->groupBy('used_for')
                    ->pluck('count', 'used_for'),
            ],
            'structure_groups' => [
                'total' => StructureOutputGroup::count(),
                'groups' => StructureOutputGroup::select('id', 'used_for')->get(),
            ],
        ]);
    }
}
