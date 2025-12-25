<?php

namespace App\Http\Controllers;

use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Services\Instagram\InstagramProfileService;
use App\Services\Instagram\InstagramPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InstagramController extends Controller
{
    public function __construct(
        protected InstagramProfileService $profileService,
        protected InstagramPostService $postService
    ) {}

    public function upsertProfile(string $username): JsonResponse
    {
        set_time_limit(120);

        try {
            $profileData = $this->profileService->getProfile($username);

            // FIXED: Wrap in transaction
            $profile = DB::transaction(function () use ($username, $profileData) {
                return InstagramProfile::updateOrCreate(
                    ['username' => $username],
                    $profileData
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Profile upserted successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upsert profile: {$username}", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to upsert Instagram profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function upsertPosts(string $igId, Request $request): JsonResponse
    {
        set_time_limit(600);

        $count = $request->input('count', 12);
        Log::info("Starting post upsert", ['ig_id' => $igId, 'count' => $count]);

        try {
            $postsData = $this->postService->getPosts($igId, $count);
            $totalPosts = count($postsData['posts']);
            $upsertedCount = 0;

            foreach ($postsData['posts'] as $index => $postData) {
                $shortcode = $postData['shortcode'] ?? 'unknown';

                if (InstagramPost::where('shortcode', $shortcode)->exists()) {
                    Log::info("Skipping existing post", ['shortcode' => $shortcode]);
                    continue;
                }

                // FIXED: Wrap each post in transaction, disable Scout sync during insert
                try {
                    DB::transaction(function () use ($postData, $igId) {
                        // Disable Scout sync during batch insert to avoid Typesense issues
                        InstagramPost::withoutSyncingToSearch(function () use ($postData, $igId) {
                            InstagramMedia::withoutSyncingToSearch(function () use ($postData, $igId) {
                                $this->processPostMedia($postData);

                                InstagramPost::create(array_merge($postData, [
                                    'ig_id' => $igId,
                                    'image' => $postData['image'],
                                ]));
                            });
                        });
                    });

                    $upsertedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to save post", [
                        'shortcode' => $shortcode,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Posts upserted successfully',
                'data' => [
                    'upserted_count' => $upsertedCount,
                    'total_posts' => $totalPosts,
                    'end_cursor' => $postsData['end_cursor']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function processPostMedia(array $postData): void
    {
        $shortcode = $postData['shortcode'] ?? 'unknown';

        // Process main images
        if (!empty($postData['image']['image_high'])) {
            $this->downloadAndSaveImage($postData['image']['image_high'], $postData, 'high', null, $postData['media_id']);
        }
        if (!empty($postData['image']['image_mid'])) {
            $this->downloadAndSaveImage($postData['image']['image_mid'], $postData, 'mid', null, $postData['media_id']);
        }

        // Process main video if exists
        if (!empty($postData['video_url'])) {
            $this->extractFrames($postData['video_url'], $postData, null, $postData['media_id']);
        }

        // Process carousel media
        if (!empty($postData['images_carousel'])) {
            foreach ($postData['images_carousel'] as $key => $carousel) {
                if ($key == 0) continue;

                $mediaId = $carousel['media_id'] ?? null;

                if (isset($carousel['image']['image_high'])) {
                    $this->downloadAndSaveImage($carousel['image']['image_high'], $postData, 'high', $key, $mediaId);
                }
                if (isset($carousel['image']['image_mid'])) {
                    $this->downloadAndSaveImage($carousel['image']['image_mid'], $postData, 'mid', $key, $mediaId);
                }

                if (isset($carousel['video_url'])) {
                    $this->extractFrames($carousel['video_url'], $postData, $key, $mediaId);
                }
            }
        }
    }

    protected function extractFrames(string $apiUrl, array $post, ?int $carouselIndex = null, ?string $mediaId = null): ?array
    {
        $shortcode = $post['shortcode'] ?? 'unknown';

        try {
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            $videoPath = $tempPath . '/' . uniqid('video_') . '.mp4';
            $videoContent = Http::timeout(60)->get($apiUrl)->body();
            file_put_contents($videoPath, $videoContent);

            $duration = $this->getVideoDuration($videoPath);
            $totalFrames = 8;
            $interval = $duration > 0 ? $duration / ($totalFrames - 1) : 1;

            $basePath = $carouselIndex !== null
                ? "posts/{$shortcode}/carousel/{$carouselIndex}"
                : "posts/{$shortcode}/video";

            $videoStoragePath = "{$basePath}/video.mp4";
            Storage::disk('public')->makeDirectory(dirname($videoStoragePath));
            Storage::disk('public')->put($videoStoragePath, $videoContent);

            $this->createMediaRecord($post, $videoStoragePath, 'video', 'video', $mediaId);

            $framePaths = [];
            for ($frame = 0; $frame < $totalFrames; $frame++) {
                $framePath = $this->extractFrame($videoPath, $shortcode, $frame, $interval, $carouselIndex);
                if ($framePath) {
                    $framePaths[] = $framePath;
                    $this->createMediaRecord($post, $framePath, 'frame', 'carousel', $mediaId);
                }
            }

            unlink($videoPath);
            return ['video_path' => $videoStoragePath, 'frame_paths' => $framePaths];
        } catch (\Exception $e) {
            Log::error("Frame extraction failed for {$shortcode}: " . $e->getMessage());
            if (isset($videoPath) && file_exists($videoPath)) {
                unlink($videoPath);
            }
            return null;
        }
    }

    protected function getVideoDuration(string $videoPath): int
    {
        $output = [];
        exec(sprintf('ffmpeg -i %s 2>&1', escapeshellarg($videoPath)), $output);

        foreach ($output as $line) {
            if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})/', $line, $matches)) {
                return $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
            }
        }
        return 0;
    }

    protected function extractFrame(string $videoPath, string $shortcode, int $frame, float $interval, ?int $carouselIndex = null): ?string
    {
        $time = round($frame * $interval);

        $framePath = $carouselIndex !== null
            ? "posts/{$shortcode}/carousel/{$carouselIndex}/frame_{$frame}.jpg"
            : "posts/{$shortcode}/video/frame_{$frame}.jpg";

        Storage::disk('public')->makeDirectory(dirname($framePath));

        $command = sprintf(
            'ffmpeg -i %s -ss %d -vframes 1 -f image2 -filter:v "scale=w=\'min(640,iw)\':h=\'min(640,ih)\':force_original_aspect_ratio=decrease" %s 2>&1',
            escapeshellarg($videoPath),
            $time,
            escapeshellarg(Storage::disk('public')->path($framePath))
        );

        exec($command);
        return file_exists(Storage::disk('public')->path($framePath)) ? $framePath : null;
    }

    protected function createMediaRecord(array $post, string $path, string $type, string $usedFor, ?string $mediaId = null): void
    {
        InstagramMedia::updateOrCreate(
            [
                'instagram_post_id' => $post['post_id'],
                'media_path' => $path,
            ],
            [
                'shortcode' => $post['shortcode'],
                'type' => $type,
                'used_for' => $usedFor,
                'status' => 'downloaded',
                'media_id' => $mediaId
            ]
        );
    }

    protected function downloadAndSaveImage(string $url, array $post, string $type = 'high', ?int $carouselId = null, ?string $mediaId = null): ?string
    {
        $shortcode = $post['shortcode'] ?? 'unknown';

        try {
            $response = Http::timeout(30)->get($url);
            if (!$response->successful()) {
                Log::warning("Failed to download {$type} image for {$shortcode}");
                return null;
            }

            $path = $carouselId
                ? "posts/{$shortcode}/carousel/{$carouselId}/{$type}.jpg"
                : "posts/{$shortcode}/{$type}.jpg";

            Storage::disk('public')->makeDirectory(dirname($path));
            Storage::disk('public')->put($path, $response->body());

            $this->createMediaRecord(
                $post,
                $path,
                $carouselId ? 'carousel_' . $type : 'image_' . $type,
                'image',
                $mediaId
            );

            return $path;
        } catch (\Exception $e) {
            Log::error("Image download failed for {$shortcode}: " . $e->getMessage());
            return null;
        }
    }
}
