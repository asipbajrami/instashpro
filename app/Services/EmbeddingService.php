<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $url;
    protected string $apiKey;
    protected string $textModel;
    protected string $imageModel;

    public function __construct()
    {
        // Use config() with null coalescing to ensure string type
        $this->url = config('services.embedding.url') ?? '';
        $this->apiKey = config('services.embedding.api_key') ?? '';
        $this->textModel = config('services.embedding.text_model') ?? 'qwen3-embedding';
        $this->imageModel = config('services.embedding.image_model') ?? 'siglip2-embedding';
    }

    /**
     * Check if embedding service is enabled
     */
    public function isEnabled(): bool
    {
        return config('services.embedding.enabled', false) && !empty($this->url);
    }

    /**
     * Get image embedding from SigLIP2 via LiteLLM
     * Accepts base64 image string
     */
    public function getImageEmbedding(?string $base64Image): ?array
    {
        if (!$base64Image || !$this->isEnabled()) {
            return null;
        }

        $startTime = microtime(true);
        $inputSize = strlen($base64Image);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url . '/v1/embeddings', [
                    'model' => $this->imageModel,
                    'input' => 'data:image/jpeg;base64,' . $base64Image,
                ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['data'][0]['embedding'] ?? null;

                Log::info('Embedding generated', [
                    'embedding.type' => 'image',
                    'embedding.model' => $this->imageModel,
                    'input.size_bytes' => $inputSize,
                    'embedding.dimensions' => $embedding ? count($embedding) : 0,
                    'duration.ms' => $durationMs,
                ]);

                return $embedding;
            }

            Log::error('Embedding failed', [
                'embedding.type' => 'image',
                'embedding.model' => $this->imageModel,
                'error.message' => $response->body(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            Log::error('Embedding exception', [
                'embedding.type' => 'image',
                'embedding.model' => $this->imageModel,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        }
    }

    /**
     * Get text embedding from SigLIP2 via LiteLLM (for cross-modal image search)
     * Note: SigLIP2 has a max token limit of 64, so text is truncated
     */
    public function getClipTextEmbedding(?string $text): ?array
    {
        if (!$text || !$this->isEnabled()) {
            return null;
        }

        $startTime = microtime(true);
        $originalWordCount = count(preg_split('/\s+/', trim($text)));

        // SigLIP2 has max 64 tokens - truncate to ~20 words to stay within limit
        $words = preg_split('/\s+/', trim($text));
        $truncated = count($words) > 20;
        if ($truncated) {
            $text = implode(' ', array_slice($words, 0, 20));
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url . '/v1/embeddings', [
                    'model' => $this->imageModel,
                    'input' => $text,
                ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['data'][0]['embedding'] ?? null;

                Log::info('Embedding generated', [
                    'embedding.type' => 'clip_text',
                    'embedding.model' => $this->imageModel,
                    'input.word_count' => $originalWordCount,
                    'input.truncated' => $truncated,
                    'embedding.dimensions' => $embedding ? count($embedding) : 0,
                    'duration.ms' => $durationMs,
                ]);

                return $embedding;
            }

            Log::error('Embedding failed', [
                'embedding.type' => 'clip_text',
                'embedding.model' => $this->imageModel,
                'error.message' => $response->body(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            Log::error('Embedding exception', [
                'embedding.type' => 'clip_text',
                'embedding.model' => $this->imageModel,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        }
    }

    /**
     * Get text embedding from Qwen3 via LiteLLM
     */
    public function getTextEmbedding(?string $text): ?array
    {
        if (!$text || !$this->isEnabled()) {
            return null;
        }

        $startTime = microtime(true);
        $inputLength = strlen($text);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url . '/v1/embeddings', [
                    'model' => $this->textModel,
                    'input' => $text,
                ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['data'][0]['embedding'] ?? null;

                Log::info('Embedding generated', [
                    'embedding.type' => 'text',
                    'embedding.model' => $this->textModel,
                    'input.length' => $inputLength,
                    'embedding.dimensions' => $embedding ? count($embedding) : 0,
                    'duration.ms' => $durationMs,
                ]);

                return $embedding;
            }

            Log::error('Embedding failed', [
                'embedding.type' => 'text',
                'embedding.model' => $this->textModel,
                'error.message' => $response->body(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            Log::error('Embedding exception', [
                'embedding.type' => 'text',
                'embedding.model' => $this->textModel,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $durationMs,
            ]);
            return null;
        }
    }
}
