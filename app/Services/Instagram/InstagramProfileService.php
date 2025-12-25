<?php

namespace App\Services\Instagram;

use Illuminate\Support\Facades\Log;

class InstagramProfileService extends InstagramBaseService
{
    public function getProfile(string $username): array
    {
        try {
            $response = $this->makeRequest("info", 'GET', [
                'username_or_id_or_url' => $username
            ]);

            if (!isset($response['data'])) {
                throw new \Exception("Invalid response format from Instagram API");
            }

            return $this->formatProfileData($response['data']);
        } catch (\Exception $e) {
            Log::error("Error fetching Instagram profile for {$username}: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatProfileData(array $data): array
    {
        return [
            'username' => $data['username'] ?? null,
            'full_name' => $data['full_name'] ?? null,
            'status' => 'active',
            'ig_id' => $data['id'] ?? null,
            'biography' => $data['biography'] ?? null,
            'bio_links' => $data['bio_links'] ?? [],
            'follower_count' => $data['follower_count'] ?? 0,
            'following_count' => $data['following_count'] ?? 0,
            'media_count' => $data['media_count'] ?? 0,
            'profile_pic_url' => $data['profile_pic_url'] ?? null,
            'profile_pic_url_hd' => $data['profile_pic_url_hd'] ?? null,
            'is_private' => $data['is_private'] ?? false,
            'is_verified' => $data['is_verified'] ?? false,
            'is_business' => $data['is_business'] ?? false,
            'business_category' => $data['business_category_name'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'category' => $data['category'] ?? null,
            'pronouns' => $data['pronouns'] ?? [],
        ];
    }
}
