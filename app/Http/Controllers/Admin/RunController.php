<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\InstagramProcessingRun;
use App\Models\InstagramScrapeRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunController extends Controller
{

    public function scrapeRuns(Request $request): JsonResponse
    {
        $query = InstagramScrapeRun::with('profile:id,username,profile_pic_url')
            ->orderBy('created_at', 'desc');

        if ($request->has('profile_id')) {
            $query->where('instagram_profile_id', $request->profile_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function processingRuns(Request $request): JsonResponse
    {
        $query = InstagramProcessingRun::with('profile:id,username,profile_pic_url')
            ->orderBy('created_at', 'desc');

        if ($request->has('profile_id')) {
            $query->where('instagram_profile_id', $request->profile_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function profileRuns(InstagramProfile $profile): JsonResponse
    {
        $scrapeRuns = $profile->scrapeRuns()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $processingRuns = $profile->processingRuns()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'profile' => [
                'id' => $profile->id,
                'username' => $profile->username,
                'media_count' => $profile->media_count,
                'local_post_count' => $profile->local_post_count,
                'coverage_percentage' => $profile->coverage_percentage,
                'initial_scrape_done' => $profile->initial_scrape_done,
                'initial_scrape_at' => $profile->initial_scrape_at,
                'posts_per_request' => $profile->posts_per_request ?? 12,
            ],
            'scrape_runs' => $scrapeRuns,
            'processing_runs' => $processingRuns,
        ]);
    }

    public function triggerScrape(InstagramProfile $profile): JsonResponse
    {
        $run = InstagramScrapeRun::create([
            'instagram_profile_id' => $profile->id,
            'type' => 'posts',
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Dispatch job for background processing
        \App\Jobs\ScrapeInstagramProfile::dispatch($profile->id, $run->id);

        return response()->json([
            'success' => true,
            'message' => 'Scrape job dispatched for background processing.',
            'data' => [
                'run' => $run->fresh(),
            ],
        ]);
    }

    public function updateProfileSettings(Request $request, InstagramProfile $profile): JsonResponse
    {
        $validated = $request->validate([
            'posts_per_request' => 'integer|min:1|max:12',
        ]);

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'data' => $profile->fresh(),
        ]);
    }

    public function triggerProcessing(InstagramProfile $profile): JsonResponse
    {
        // Get unprocessed posts for this profile
        $posts = InstagramPost::where('username', $profile->username)
            ->where('processed_structure', false)
            ->get();

        if ($posts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No unprocessed posts found for this profile',
            ]);
        }

        // Create a processing run to track progress
        $run = InstagramProcessingRun::create([
            'instagram_profile_id' => $profile->id,
            'username' => $profile->username,
            'status' => 'running',
            'posts_to_process' => $posts->count(),
            'posts_processed' => 0,
            'posts_skipped' => 0,
            'posts_failed' => 0,
            'started_at' => now(),
        ]);

        // Dispatch jobs for each post (background processing)
        foreach ($posts as $post) {
            \App\Jobs\ProcessInstagramPost::dispatch($post->id, $run->id);
        }

        return response()->json([
            'success' => true,
            'message' => "Dispatched {$posts->count()} posts for background processing. Run 'php artisan queue:work' to process.",
            'data' => [
                'run' => $run->fresh(),
                'posts_queued' => $posts->count(),
            ],
        ]);
    }

    public function triggerLabeling(InstagramProfile $profile): JsonResponse
    {
        // Get unlabeled posts count for this profile
        $postsToLabel = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->count();

        if ($postsToLabel === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No unlabeled posts found for this profile',
            ]);
        }

        // Dispatch job for background processing
        \App\Jobs\LabelInstagramPosts::dispatch($profile->id);

        return response()->json([
            'success' => true,
            'message' => "Labeling job dispatched for {$postsToLabel} posts.",
            'data' => [
                'posts_to_label' => $postsToLabel,
            ],
        ]);
    }

    public function labelingStatus(InstagramProfile $profile): JsonResponse
    {
        $unlabeledCount = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->count();

        $totalPosts = InstagramPost::where('username', $profile->username)->count();

        return response()->json([
            'profile_id' => $profile->id,
            'unlabeled_count' => $unlabeledCount,
            'total_posts' => $totalPosts,
            'is_complete' => $unlabeledCount === 0,
        ]);
    }

    public function triggerFullPipeline(InstagramProfile $profile): JsonResponse
    {
        // Create a scrape run to track the pipeline
        $run = InstagramScrapeRun::create([
            'instagram_profile_id' => $profile->id,
            'type' => 'full_pipeline',
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Dispatch the full pipeline job
        \App\Jobs\FullPipelineJob::dispatch($profile->id, $run->id);

        return response()->json([
            'success' => true,
            'message' => 'Full pipeline (scrape → label → process) dispatched for background processing.',
            'data' => [
                'run' => $run->fresh(),
            ],
        ]);
    }
}
