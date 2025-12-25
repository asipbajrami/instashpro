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
        $profile = InstagramProfile::find($this->profileId);

        if (!$profile) {
            Log::warning("LabelInstagramPosts: Profile {$this->profileId} not found");
            return;
        }

        $posts = InstagramPost::where('username', $profile->username)
            ->whereNull('used_for')
            ->get();

        if ($posts->isEmpty()) {
            Log::info("LabelInstagramPosts: No unlabeled posts for {$profile->username}");
            return;
        }

        $labeled = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                $group = $this->classifyPost($post, $llmService);
                $post->update(['used_for' => $group]);
                $labeled++;
            } catch (Exception $e) {
                Log::error("LabelInstagramPosts: Failed to label post {$post->id}", [
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }
        }

        Log::info("LabelInstagramPosts: Completed for {$profile->username}", [
            'total' => $posts->count(),
            'labeled' => $labeled,
            'errors' => $errors,
        ]);
    }

    private function classifyPost(InstagramPost $post, LlmServiceInterface $llmService): string
    {
        $base64Image = null;

        // Get first valid image for classification
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

        return $llmService->classifyPostCategory($post->caption, $base64Image, 'tech');
    }

    public function failed(Exception $exception): void
    {
        Log::error("LabelInstagramPosts: Job failed for profile {$this->profileId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
