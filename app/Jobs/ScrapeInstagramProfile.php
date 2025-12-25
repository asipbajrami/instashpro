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
        $profile = InstagramProfile::find($this->profileId);
        $run = InstagramScrapeRun::find($this->runId);

        if (!$profile || !$run) {
            Log::warning("ScrapeInstagramProfile: Profile {$this->profileId} or Run {$this->runId} not found");
            return;
        }

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
                    Log::error("Failed to save post during scrape run", [
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
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $profile->updateLocalPostCount();

            if ($isFirstScrape) {
                $profile->update([
                    'initial_scrape_done' => true,
                    'initial_scrape_at' => now(),
                ]);
            }

            Log::info("ScrapeInstagramProfile: Completed for {$profile->username}", [
                'posts_fetched' => $postsFetched,
                'posts_new' => $postsNew,
                'posts_skipped' => $postsSkipped,
            ]);

        } catch (Exception $e) {
            Log::error("ScrapeInstagramProfile: Failed for {$profile->username}", [
                'error' => $e->getMessage(),
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
        Log::error("ScrapeInstagramProfile: Job failed for profile {$this->profileId}", [
            'error' => $exception->getMessage()
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
