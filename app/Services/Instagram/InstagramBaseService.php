<?php

namespace App\Services\Instagram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InstagramBaseService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiHost;

    public function __construct()
    {
        // FIXED: Use config() for all values
        $this->baseUrl = config('services.instagram_scraper.base_url');
        $this->apiKey = config('services.instagram_scraper.api_key');
        $this->apiHost = config('services.instagram_scraper.api_host');

        if (empty($this->apiKey)) {
            throw new RuntimeException('Instagram Scraper API key is not configured in services.instagram_scraper.api_key');
        }
    }

    protected function makeRequest(string $endpoint, string $method = 'GET', array $params = []): array
    {
        $startTime = microtime(true);
        $username = $params['username'] ?? $params['username_or_id'] ?? null;

        Log::info('Instagram API request', [
            'api.endpoint' => $endpoint,
            'api.method' => $method,
            'profile.username' => $username,
        ]);

        try {
            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withHeaders([
                    'x-rapidapi-host' => $this->apiHost,
                    'x-rapidapi-key' => $this->apiKey,
                ])->get($this->baseUrl . $endpoint, $params);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::error('Instagram API failed', [
                    'api.endpoint' => $endpoint,
                    'profile.username' => $username,
                    'response.status' => $response->status(),
                    'error.message' => $response->body(),
                    'duration.ms' => $durationMs,
                ]);
                throw new RuntimeException("Instagram API request failed: " . $response->status());
            }

            Log::info('Instagram API response', [
                'api.endpoint' => $endpoint,
                'profile.username' => $username,
                'response.status' => $response->status(),
                'duration.ms' => $durationMs,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            Log::error('Instagram API exception', [
                'api.endpoint' => $endpoint,
                'profile.username' => $username,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
                'duration.ms' => $durationMs,
            ]);
            throw $e;
        }
    }
}
