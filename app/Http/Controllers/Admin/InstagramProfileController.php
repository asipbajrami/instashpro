<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramProfile;
use App\Services\Instagram\InstagramProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstagramProfileController extends Controller
{
    public function __construct(
        protected InstagramProfileService $profileService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $query = InstagramProfile::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }

        $profiles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($profiles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:instagram_profiles,username',
            'scrape_interval_hours' => 'integer|in:1,2,4,6,12,24',
            'status' => 'string|in:active,inactive,suspended',
        ]);

        try {
            // Fetch real profile data from Instagram API
            $igData = $this->profileService->getProfile($validated['username']);

            $profile = InstagramProfile::create([
                'username' => $igData['username'] ?? $validated['username'],
                'full_name' => $igData['full_name'],
                'ig_id' => $igData['ig_id'] ?? '',
                'biography' => $igData['biography'],
                'bio_links' => $igData['bio_links'] ?? [],
                'follower_count' => $igData['follower_count'] ?? 0,
                'following_count' => $igData['following_count'] ?? 0,
                'media_count' => $igData['media_count'] ?? 0,
                'profile_pic_url' => $igData['profile_pic_url'],
                'profile_pic_url_hd' => $igData['profile_pic_url_hd'] ?? null,
                'is_private' => $igData['is_private'] ?? false,
                'is_verified' => $igData['is_verified'] ?? false,
                'is_business' => $igData['is_business'] ?? false,
                'business_category' => $igData['business_category'] ?? null,
                'external_url' => $igData['external_url'] ?? null,
                'category' => $igData['category'] ?? null,
                'pronouns' => $igData['pronouns'] ?? [],
                'scrape_interval_hours' => $validated['scrape_interval_hours'] ?? 24,
                'status' => $validated['status'] ?? 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile added successfully with Instagram data',
                'data' => $profile,
            ], 201);

        } catch (\Exception $e) {
            // If API fails, create profile with minimal data
            $profile = InstagramProfile::create([
                'username' => $validated['username'],
                'ig_id' => '',
                'scrape_interval_hours' => $validated['scrape_interval_hours'] ?? 24,
                'status' => $validated['status'] ?? 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile added, but could not fetch Instagram data: ' . $e->getMessage(),
                'data' => $profile,
            ], 201);
        }
    }

    public function show(InstagramProfile $instagramProfile): JsonResponse
    {
        return response()->json($instagramProfile);
    }

    public function update(Request $request, InstagramProfile $instagramProfile): JsonResponse
    {
        $validated = $request->validate([
            'username' => [
                'string',
                Rule::unique('instagram_profiles')->ignore($instagramProfile->id),
            ],
            'scrape_interval_hours' => 'integer|in:1,2,4,6,12,24',
            'status' => 'string|in:active,inactive,suspended',
        ]);

        $instagramProfile->update($validated);

        if (isset($validated['scrape_interval_hours'])) {
            $instagramProfile->update([
                'next_scrape_at' => $instagramProfile->last_scraped_at
                    ? $instagramProfile->last_scraped_at->addHours($validated['scrape_interval_hours'])
                    : now(),
            ]);
        }

        return response()->json($instagramProfile);
    }

    public function destroy(InstagramProfile $instagramProfile): JsonResponse
    {
        $instagramProfile->delete();

        return response()->json(null, 204);
    }
}
