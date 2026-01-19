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
        $startTime = microtime(true);
        $post = InstagramPost::find($this->postId);

        if (!$post) {
            Log::warning('Post processing skipped - post not found', [
                // Job context
                'job.type' => 'process_post',
                'post.id' => $this->postId,
                'run.id' => $this->runId,

                // Result
                'status' => 'failed',
                'skip.reason' => 'post_not_found',
            ]);
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

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            // Build wide event with all context
            $llmMetadata = $result['_llm_metadata'] ?? [];
            $classificationMeta = $llmMetadata['classification'] ?? [];
            $extractionMeta = $llmMetadata['extraction'] ?? [];

            if ($result['success']) {
                Log::info('Post processed', [
                    // Job context
                    'job.type' => 'process_post',
                    'post.id' => $this->postId,
                    'post.shortcode' => $post->shortcode,
                    'run.id' => $this->runId,

                    // Result
                    'status' => 'success',
                    'products.created' => $result['products_created'] ?? 0,
                    'products.skipped_low_confidence' => $result['products_skipped_low_confidence'] ?? 0,

                    // Classification details
                    'classification.category' => $result['group'] ?? null,
                    'classification.model' => $classificationMeta['model'] ?? null,
                    'classification.provider' => $classificationMeta['provider'] ?? null,
                    'classification.duration_ms' => $classificationMeta['duration_ms'] ?? null,
                    'classification.fallback' => $classificationMeta['fallback'] ?? false,

                    // Extraction details
                    'extraction.model' => $extractionMeta['model'] ?? null,
                    'extraction.provider' => $extractionMeta['provider'] ?? null,
                    'extraction.images_count' => $extractionMeta['images_count'] ?? null,
                    'extraction.has_products' => true,
                    'extraction.duration_ms' => $extractionMeta['duration_ms'] ?? null,
                    'extraction.attempts' => $llmMetadata['extraction_attempts'] ?? 1,

                    // Timing
                    'duration.total_ms' => $durationMs,
                ]);
                $this->updateRunStats('processed');
            } else {
                Log::info('Post skipped', [
                    // Job context
                    'job.type' => 'process_post',
                    'post.id' => $this->postId,
                    'post.shortcode' => $post->shortcode,
                    'run.id' => $this->runId,

                    // Result
                    'status' => 'skipped',
                    'skip.reason' => $result['reason'] ?? 'unknown',

                    // Classification details (if available)
                    'classification.category' => $result['group'] ?? null,
                    'classification.model' => $classificationMeta['model'] ?? null,
                    'classification.provider' => $classificationMeta['provider'] ?? null,
                    'classification.duration_ms' => $classificationMeta['duration_ms'] ?? null,
                    'classification.fallback' => $classificationMeta['fallback'] ?? false,

                    // Extraction details (if available)
                    'extraction.model' => $extractionMeta['model'] ?? null,
                    'extraction.provider' => $extractionMeta['provider'] ?? null,
                    'extraction.images_count' => $extractionMeta['images_count'] ?? null,
                    'extraction.has_products' => false,
                    'extraction.products_count' => 0,
                    'extraction.duration_ms' => $extractionMeta['duration_ms'] ?? null,
                    'extraction.attempts' => $llmMetadata['extraction_attempts'] ?? null,

                    // Timing
                    'duration.total_ms' => $durationMs,
                ]);
                $this->updateRunStats('skipped');
            }
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            // Try to extract LLM metadata from JSON-encoded exception message
            $errorMessage = $e->getMessage();
            $llmErrorMeta = [];
            if (str_starts_with($errorMessage, '{')) {
                $decoded = json_decode($errorMessage, true);
                if ($decoded && isset($decoded['_metadata'])) {
                    $llmErrorMeta = $decoded['_metadata'];
                    $errorMessage = $decoded['message'] ?? $errorMessage;
                }
            }

            Log::error('Post processing failed', [
                // Job context
                'job.type' => 'process_post',
                'post.id' => $this->postId,
                'post.shortcode' => $post->shortcode ?? null,
                'run.id' => $this->runId,

                // Result
                'status' => 'failed',

                // Error details
                'error.type' => get_class($e),
                'error.message' => $errorMessage,

                // LLM context (if available from exception)
                'llm.provider' => $llmErrorMeta['provider'] ?? null,
                'llm.model' => $llmErrorMeta['model'] ?? null,
                'llm.duration_ms' => $llmErrorMeta['duration_ms'] ?? null,

                // Timing
                'duration.total_ms' => $durationMs,
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
        Log::error('Post processing exhausted retries', [
            // Job context
            'job.type' => 'process_post',
            'post.id' => $this->postId,
            'run.id' => $this->runId,

            // Result
            'status' => 'failed',
            'retries_exhausted' => true,

            // Error details
            'error.type' => get_class($exception),
            'error.message' => $exception->getMessage(),
        ]);
        $this->updateRunStats('failed');
    }
}
