<?php

namespace App\Jobs;

use App\Http\Controllers\InstagramController;
use App\Http\Controllers\ProductProcessorController;
use App\Models\Category;
use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\InstagramProcessingRun;
use App\Models\InstagramScrapeRun;
use App\Models\ProductAttributeValue;
use App\Services\Instagram\InstagramPostService;
use App\Services\Llm\LlmServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FullPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes for full pipeline
    public int $backoff = 60; // Retry after 1 minute on failure

    public function __construct(
        public int $profileId,
        public int $scrapeRunId
    ) {}

    public function handle(
        InstagramPostService $postService,
        InstagramController $instagramController,
        LlmServiceInterface $llmService,
        ProductProcessorController $productProcessor
    ): void {
        $profile = InstagramProfile::find($this->profileId);
        $scrapeRun = InstagramScrapeRun::find($this->scrapeRunId);

        if (!$profile || !$scrapeRun) {
            Log::warning("FullPipelineJob: Profile {$this->profileId} or Run {$this->scrapeRunId} not found");
            return;
        }

        try {
            // Step 1: Scrape
            Log::info("FullPipelineJob: [1/3] SCRAPE starting for {$profile->username}");
            $scrapeRun->update(['error_message' => 'Step 1/3: Scraping...']);
            $this->runScrape($profile, $scrapeRun, $postService, $instagramController);

            // Step 2: Label
            Log::info("FullPipelineJob: [2/3] LABEL starting for {$profile->username}");
            $scrapeRun->update(['error_message' => 'Step 2/3: Labeling...']);
            $this->runLabeling($profile, $llmService);

            // Step 3: Process
            Log::info("FullPipelineJob: [3/3] PROCESS starting for {$profile->username}");
            $scrapeRun->update(['error_message' => 'Step 3/3: Processing...']);
            $this->runProcessing($profile, $productProcessor);

            // Mark complete
            $scrapeRun->update([
                'status' => 'completed',
                'error_message' => null,
                'completed_at' => now(),
            ]);

            Log::info("FullPipelineJob: COMPLETED full pipeline for {$profile->username}");

        } catch (Exception $e) {
            Log::error("FullPipelineJob: Failed for {$profile->username}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $scrapeRun->update([
                'status' => 'failed',
                'error_message' => 'Pipeline failed: ' . $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    private function runScrape(
        InstagramProfile $profile,
        InstagramScrapeRun $run,
        InstagramPostService $postService,
        InstagramController $instagramController
    ): void {
        $isFirstScrape = !$profile->initial_scrape_done;

        $limit = $profile->posts_per_request ?? 12;
        $identifier = $profile->ig_id ?: $profile->username;
        $result = $postService->getPosts($identifier, $limit);

        $postsNew = 0;
        $postsSkipped = 0;
        $postsFetched = count($result['posts']);

        foreach ($result['posts'] as $postData) {
            $shortcode = $postData['shortcode'] ?? null;
            if (!$shortcode) continue;

            if (InstagramPost::where('shortcode', $shortcode)->exists()) {
                $postsSkipped++;
                continue;
            }

            try {
                $instagramController->processPostMedia($postData);
                InstagramPost::create(array_merge($postData, [
                    'ig_id' => $profile->ig_id,
                    'image' => $postData['image'],
                ]));
                $postsNew++;
            } catch (Exception $e) {
                Log::error("FullPipelineJob: Failed to save post", [
                    'shortcode' => $shortcode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update([
            'posts_fetched' => $postsFetched,
            'posts_new' => $postsNew,
            'posts_skipped' => $postsSkipped,
            'end_cursor' => $result['end_cursor'] ?? null,
            'has_more_pages' => $result['has_more'] ?? false,
        ]);

        $profile->updateLocalPostCount();

        if ($isFirstScrape) {
            $profile->update([
                'initial_scrape_done' => true,
                'initial_scrape_at' => now(),
            ]);
        }

        Log::info("FullPipelineJob: Scrape completed", [
            'posts_fetched' => $postsFetched,
            'posts_new' => $postsNew,
        ]);
    }

    private function runLabeling(InstagramProfile $profile, LlmServiceInterface $llmService): void
    {
        $posts = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->get();

        if ($posts->isEmpty()) {
            Log::info("FullPipelineJob: No posts to label for {$profile->username}");
            return;
        }

        $labeled = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                // Same logic as LabelInstagramPosts job
                $base64Image = null;
                $media = InstagramMedia::whereIn('type', ['carousel_mid', 'image_mid', 'carousel_high', 'image_high'])
                    ->where('instagram_post_id', $post->post_id)
                    ->orderBy('media_id')
                    ->first();

                if ($media && Storage::disk('public')->exists($media->media_path)) {
                    $mimeType = Storage::disk('public')->mimeType($media->media_path);
                    if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                        $base64Image = base64_encode(Storage::disk('public')->get($media->media_path));
                    }
                }

                $group = $llmService->classifyPostCategory($post->caption, $base64Image, 'tech');
                $post->update(['used_for' => $group]);
                $labeled++;
            } catch (Exception $e) {
                $errors++;
                Log::error("FullPipelineJob: Failed to label post {$post->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("FullPipelineJob: Labeled {$labeled}/{$posts->count()} posts for {$profile->username} (errors: {$errors})");
    }

    private function runProcessing(InstagramProfile $profile, ProductProcessorController $productProcessor): void
    {
        $posts = InstagramPost::where('username', $profile->username)
            ->where('processed_structure', false)
            ->get();

        if ($posts->isEmpty()) {
            Log::info("FullPipelineJob: No posts to process for {$profile->username}");
            return;
        }

        // Create processing run for tracking
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

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($posts as $post) {
            try {
                // Same logic as ProcessInstagramPost job
                $result = DB::transaction(function () use ($post, $productProcessor) {
                    return InstagramPost::withoutSyncingToSearch(function () use ($post, $productProcessor) {
                        return InstagramMedia::withoutSyncingToSearch(function () use ($post, $productProcessor) {
                            return Category::withoutSyncingToSearch(function () use ($post, $productProcessor) {
                                return ProductAttributeValue::withoutSyncingToSearch(function () use ($post, $productProcessor) {
                                    return $productProcessor->processPostPublic($post);
                                });
                            });
                        });
                    });
                });

                if ($result['success']) {
                    $processed++;
                    $run->update(['posts_processed' => $processed]);
                } else {
                    $skipped++;
                    $run->update(['posts_skipped' => $skipped]);
                }
            } catch (Exception $e) {
                $failed++;
                $run->update(['posts_failed' => $failed]);
                Log::error("FullPipelineJob: Failed to process post {$post->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info("FullPipelineJob: Processed {$processed}/{$posts->count()} posts (skipped: {$skipped}, failed: {$failed})");
    }

    public function failed(Exception $exception): void
    {
        Log::error("FullPipelineJob: Job failed for profile {$this->profileId}", [
            'error' => $exception->getMessage()
        ]);

        $run = InstagramScrapeRun::find($this->scrapeRunId);
        if ($run) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'Pipeline failed: ' . $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
