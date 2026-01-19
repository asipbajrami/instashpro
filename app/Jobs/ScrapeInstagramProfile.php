<?php

namespace App\Jobs;

use App\Http\Controllers\InstagramController;
use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\InstagramScrapeRun;
use App\Services\Instagram\InstagramPostService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeInstagramProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600; // 10 minutes for image downloads

    public function __construct(
        public int $profileId,
        public int $runId
    ) {}

    public function handle(InstagramPostService $postService, InstagramController $instagramController): void
    {
        $startTime = microtime(true);
        $profile = InstagramProfile::find($this->profileId);
        $run = InstagramScrapeRun::find($this->runId);

        if (!$profile || !$run) {
            Log::warning('Scrape profile not found', [
                'job.type' => 'scrape',
                'profile.id' => $this->profileId,
                'run.id' => $this->runId,
            ]);
            return;
        }

        Log::info('Scrape started', [
            'job.type' => 'scrape',
            'profile.id' => $this->profileId,
            'profile.username' => $profile->username,
            'run.id' => $this->runId,
        ]);

        $isFirstScrape = !$profile->initial_scrape_done;

        try {
            $limit = $profile->posts_per_request ?? 12;
            $identifier = $profile->ig_id ?: $profile->username;
            $result = $postService->getPosts($identifier, $limit);

            $postsNew = 0;
            $postsSkipped = 0;
            $postsFetched = count($result['posts']);

            foreach ($result['posts'] as $postData) {
                $shortcode = $postData['shortcode'] ?? null;

                if (!$shortcode) {
                    continue;
                }

                if (InstagramPost::where('shortcode', $shortcode)->exists()) {
                    $postsSkipped++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($postData, $profile, $instagramController) {
                        InstagramPost::withoutSyncingToSearch(function () use ($postData, $profile, $instagramController) {
                            InstagramMedia::withoutSyncingToSearch(function () use ($postData, $profile, $instagramController) {
                                $instagramController->processPostMedia($postData);

                                InstagramPost::create(array_merge($postData, [
                                    'ig_id' => $profile->ig_id,
                                    'image' => $postData['image'],
                                ]));
                            });
                        });
                    });
                    $postsNew++;
                } catch (Exception $e) {
                    Log::error('Post save failed during scrape', [
                        'job.type' => 'scrape',
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
                'status' => 'completed',
                'completed_at' => now(),
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

            $durationMs = round((microtime(true) - $startTime) * 1000);

            Log::info('Scrape completed', [
                'job.type' => 'scrape',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'run.id' => $this->runId,
                'posts.fetched' => $postsFetched,
                'posts.new' => $postsNew,
                'posts.skipped' => $postsSkipped,
                'pagination.has_more' => $result['has_more'] ?? false,
                'is_first_scrape' => $isFirstScrape,
                'duration.ms' => $durationMs,
            ]);

        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);

            Log::error('Scrape failed', [
                'job.type' => 'scrape',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
                'run.id' => $this->runId,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $durationMs,
            ]);

            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Scrape job failed (all retries exhausted)', [
            'job.type' => 'scrape',
            'profile.id' => $this->profileId,
            'run.id' => $this->runId,
            'error.type' => get_class($exception),
            'error.message' => $exception->getMessage(),
        ]);

        $run = InstagramScrapeRun::find($this->runId);
        if ($run) {
            $run->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
