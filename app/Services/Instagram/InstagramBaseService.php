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
        try {
            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withHeaders([
                    'x-rapidapi-host' => $this->apiHost,
                    'x-rapidapi-key' => $this->apiKey,
                ])->get($this->baseUrl . $endpoint, $params);

            if (!$response->successful()) {
                Log::error("Instagram API error", [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new RuntimeException("Instagram API request failed: " . $response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Instagram API request error", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
