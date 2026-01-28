<?php

namespace App\Jobs;

use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Services\Llm\LlmServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\InstagramMedia;

class LabelInstagramPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // Retry after 1 minute on failure

    public function __construct(
        public int $profileId
    ) {}

    public function handle(LlmServiceInterface $llmService): void
    {
        $startTime = microtime(true);
        $profile = InstagramProfile::find($this->profileId);

        if (!$profile) {
            Log::warning('Labeling profile not found', [
                'job.type' => 'label',
                'profile.id' => $this->profileId,
            ]);
            return;
        }

        $posts = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->get();

        if ($posts->isEmpty()) {
            Log::info('Labeling skipped (no unlabeled posts)', [
                'job.type' => 'label',
                'profile.id' => $this->profileId,
                'profile.username' => $profile->username,
            ]);
            return;
        }

        Log::info('Labeling started', [
            'job.type' => 'label',
            'profile.id' => $this->profileId,
            'profile.username' => $profile->username,
            'posts.total' => $posts->count(),
        ]);

        $labeled = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                $group = $this->classifyPost($post, $llmService);
                $post->update(['used_for' => $group]);
                $labeled++;
            } catch (Exception $e) {
                Log::error('Post labeling failed', [
                    'job.type' => 'label',
                    'profile.username' => $profile->username,
                    'post.id' => $post->id,
                    'error.message' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        Log::info('Labeling completed', [
            'job.type' => 'label',
            'profile.id' => $this->profileId,
            'profile.username' => $profile->username,
            'posts.total' => $posts->count(),
            'posts.labeled' => $labeled,
            'posts.errors' => $errors,
            'duration.ms' => $durationMs,
        ]);
    }

    private function classifyPost(InstagramPost $post, LlmServiceInterface $llmService): string
    {
        $base64Image = null;

        // Get first valid image for classification
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

        return $llmService->classifyPostCategory($post->caption, $base64Image, 'tech');
    }

    public function failed(Exception $exception): void
    {
        Log::error('Labeling job failed (all retries exhausted)', [
            'job.type' => 'label',
            'profile.id' => $this->profileId,
            'error.type' => get_class($exception),
            'error.message' => $exception->getMessage(),
        ]);
    }
}
