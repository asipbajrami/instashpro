<?php

namespace App\Jobs;

use App\Http\Controllers\ProductProcessorController;
use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProcessingRun;
use App\Models\Category;
use App\Models\ProductAttributeValue;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInstagramPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes per post
    public int $backoff = 60; // Retry after 1 minute on failure

    public function __construct(
        public int $postId,
        public ?int $runId = null
    ) {}

    public function handle(ProductProcessorController $processor): void
    {
        $post = InstagramPost::find($this->postId);

        if (!$post) {
            Log::warning("ProcessInstagramPost: Post {$this->postId} not found");
            $this->updateRunStats('failed');
            return;
        }

        try {
            // Wrap in transaction and disable Scout sync
            $result = DB::transaction(function () use ($post, $processor) {
                return InstagramPost::withoutSyncingToSearch(function () use ($post, $processor) {
                    return InstagramMedia::withoutSyncingToSearch(function () use ($post, $processor) {
                        return Category::withoutSyncingToSearch(function () use ($post, $processor) {
                            return ProductAttributeValue::withoutSyncingToSearch(function () use ($post, $processor) {
                                return $processor->processPostPublic($post);
                            });
                        });
                    });
                });
            });

            if ($result['success']) {
                Log::info("ProcessInstagramPost: Successfully processed post {$this->postId}", [
                    'products_created' => $result['products_created'] ?? 0
                ]);
                $this->updateRunStats('processed');
            } else {
                Log::info("ProcessInstagramPost: Skipped post {$this->postId}", [
                    'reason' => $result['reason'] ?? 'unknown'
                ]);
                $this->updateRunStats('skipped');
            }
        } catch (Exception $e) {
            Log::error("ProcessInstagramPost: Failed to process post {$this->postId}", [
                'error' => $e->getMessage()
            ]);
            $this->updateRunStats('failed');
            throw $e; // Re-throw to trigger retry
        }
    }

    private function updateRunStats(string $status): void
    {
        if (!$this->runId) {
            return;
        }

        $run = InstagramProcessingRun::find($this->runId);
        if (!$run) {
            return;
        }

        match ($status) {
            'processed' => $run->increment('posts_processed'),
            'skipped' => $run->increment('posts_skipped'),
            'failed' => $run->increment('posts_failed'),
            default => null
        };

        // Check if all posts are done and mark run as completed
        $run->refresh();
        $totalDone = $run->posts_processed + $run->posts_skipped + $run->posts_failed;
        if ($totalDone >= $run->posts_to_process && $run->status === 'running') {
            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("ProcessInstagramPost: Job failed for post {$this->postId}", [
            'error' => $exception->getMessage()
        ]);
        $this->updateRunStats('failed');
    }
}
