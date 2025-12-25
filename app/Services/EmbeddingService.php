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
        // FIXED: Use config() exclusively
        $this->url = config('services.embedding.url', '');
        $this->apiKey = config('services.embedding.api_key', '');
        $this->textModel = config('services.embedding.text_model', 'qwen3-embedding');
        $this->imageModel = config('services.embedding.image_model', 'siglip2-embedding');
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

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::error('Image embedding failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Image embedding error: ' . $e->getMessage());
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

        // SigLIP2 has max 64 tokens - truncate to ~20 words to stay within limit
        $words = preg_split('/\s+/', trim($text));
        if (count($words) > 20) {
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

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::error('CLIP text embedding failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('CLIP text embedding error: ' . $e->getMessage());
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

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::error('Text embedding failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Text embedding error: ' . $e->getMessage());
            return null;
        }
    }
}
