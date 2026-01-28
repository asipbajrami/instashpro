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
        $pipelineStart = microtime(true);
        $profile = InstagramProfile::find($this->profileId);
        $scrapeRun = InstagramScrapeRun::find($this->scrapeRunId);

        if (!$profile || !$scrapeRun) {
            Log::warning('Pipeline profile not found', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'run.id' => $this->scrapeRunId,
            ]);
            return;
        }

        Log::info('Pipeline started', [
            'job.type' => 'full_pipeline',
            'profile.id' => $this->profileId,
            'profile.username' => $profile->username,
            'run.id' => $this->scrapeRunId,
        ]);

        try {
            // Step 1: Scrape
            $phaseStart = microtime(true);
            $scrapeRun->update(['error_message' => 'Step 1/3: Scraping...']);
            $scrapeStats = $this->runScrape($profile, $scrapeRun, $postService, $instagramController);
            $scrapeMs = round((microtime(true) - $phaseStart) * 1000);

            Log::info('Pipeline phase completed', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'phase' => 'scrape',
                'posts.fetched' => $scrapeStats['fetched'],
                'posts.new' => $scrapeStats['new'],
                'posts.skipped' => $scrapeStats['skipped'],
                'duration.ms' => $scrapeMs,
            ]);

            // Step 2: Label
            $phaseStart = microtime(true);
            $scrapeRun->update(['error_message' => 'Step 2/3: Labeling...']);
            $labelStats = $this->runLabeling($profile, $llmService);
            $labelMs = round((microtime(true) - $phaseStart) * 1000);

            Log::info('Pipeline phase completed', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'phase' => 'label',
                'posts.total' => $labelStats['total'],
                'posts.labeled' => $labelStats['labeled'],
                'posts.errors' => $labelStats['errors'],
                'duration.ms' => $labelMs,
            ]);

            // Step 3: Process
            $phaseStart = microtime(true);
            $scrapeRun->update(['error_message' => 'Step 3/3: Processing...']);
            $processStats = $this->runProcessing($profile, $productProcessor);
            $processMs = round((microtime(true) - $phaseStart) * 1000);

            Log::info('Pipeline phase completed', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'phase' => 'process',
                'posts.total' => $processStats['total'],
                'posts.processed' => $processStats['processed'],
                'posts.skipped' => $processStats['skipped'],
                'posts.failed' => $processStats['failed'],
                'duration.ms' => $processMs,
            ]);

            // Mark complete
            $scrapeRun->update([
                'status' => 'completed',
                'error_message' => null,
                'completed_at' => now(),
            ]);

            $totalMs = round((microtime(true) - $pipelineStart) * 1000);

            Log::info('Pipeline completed', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'run.id' => $this->scrapeRunId,
                'posts.scraped' => $scrapeStats['new'],
                'posts.labeled' => $labelStats['labeled'],
                'posts.processed' => $processStats['processed'],
                'duration.total_ms' => $totalMs,
                'duration.scrape_ms' => $scrapeMs,
                'duration.label_ms' => $labelMs,
                'duration.process_ms' => $processMs,
            ]);

        } catch (Exception $e) {
            $totalMs = round((microtime(true) - $pipelineStart) * 1000);

            Log::error('Pipeline failed', [
                'job.type' => 'full_pipeline',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'run.id' => $this->scrapeRunId,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $totalMs,
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
    ): array {
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
                Log::error('Post save failed', [
                    'job.type' => 'full_pipeline',
                    'profile.username' => $profile->username,
                    'post.shortcode' => $shortcode,
                    'error.message' => $e->getMessage(),
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

        // Always update last_scraped_at
        $profile->update(['last_scraped_at' => now()]);

        if ($isFirstScrape) {
            $profile->update([
                'initial_scrape_done' => true,
                'initial_scrape_at' => now(),
            ]);
        }

        return [
            'fetched' => $postsFetched,
            'new' => $postsNew,
            'skipped' => $postsSkipped,
        ];
    }

    private function runLabeling(InstagramProfile $profile, LlmServiceInterface $llmService): array
    {
        $posts = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->get();

        $total = $posts->count();

        if ($posts->isEmpty()) {
            return ['total' => 0, 'labeled' => 0, 'errors' => 0];
        }

        $labeled = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                // Same logic as LabelInstagramPosts job
                $base64Image = null;
                $media = InstagramMedia::whereIn('type', ['carousel_high', 'image_high'])
                    ->where('instagram_post_id', $post->post_id)
                    ->orderBy('media_id')
                    ->first();

                if ($media && Storage::disk('r2')->exists($media->media_path)) {
                    $mimeType = Storage::disk('r2')->mimeType($media->media_path);
                    if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                        $base64Image = base64_encode(Storage::disk('r2')->get($media->media_path));
                    }
                }

                $group = $llmService->classifyPostCategory($post->caption, $base64Image, 'tech');
                $post->update(['used_for' => $group]);
                $labeled++;
            } catch (Exception $e) {
                $errors++;
                Log::error('Post labeling failed', [
                    'job.type' => 'full_pipeline',
                    'profile.username' => $profile->username,
                    'post.id' => $post->id,
                    'error.message' => $e->getMessage(),
                ]);
            }
        }

        return ['total' => $total, 'labeled' => $labeled, 'errors' => $errors];
    }

    private function runProcessing(InstagramProfile $profile, ProductProcessorController $productProcessor): array
    {
        $posts = InstagramPost::where('username', $profile->username)
            ->where('processed_structure', false)
            ->get();

        $total = $posts->count();

        if ($posts->isEmpty()) {
            return ['total' => 0, 'processed' => 0, 'skipped' => 0, 'failed' => 0];
        }

        // Create processing run for tracking
        $run = InstagramProcessingRun::create([
            'instagram_profile_id' => $profile->id,
            'username' => $profile->username,
            'status' => 'running',
            'posts_to_process' => $total,
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
                Log::error('Post processing failed', [
                    'job.type' => 'full_pipeline',
                    'profile.username' => $profile->username,
                    'post.id' => $post->id,
                    'error.message' => $e->getMessage(),
                ]);
            }
        }

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return ['total' => $total, 'processed' => $processed, 'skipped' => $skipped, 'failed' => $failed];
    }

    public function failed(Exception $exception): void
    {
        Log::error('Pipeline job failed (all retries exhausted)', [
            'job.type' => 'full_pipeline',
            'profile.id' => $this->profileId,
            'run.id' => $this->scrapeRunId,
            'error.type' => get_class($exception),
            'error.message' => $exception->getMessage(),
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
