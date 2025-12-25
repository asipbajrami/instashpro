<?php

namespace App\Services\Instagram;

use Illuminate\Support\Facades\Log;

class InstagramPostService extends InstagramBaseService
{
    public function getPosts(string $usernameOrIdOrUrl, int $count = 12, ?string $endCursor = null): array
    {
        try {
            $params = [
                'username_or_id_or_url' => $usernameOrIdOrUrl,
                'amount' => $count,
            ];

            if ($endCursor) {
                $params['pagination_token'] = $endCursor;
            }

            $data = $this->makeRequest("posts", 'GET', $params);

            if (!isset($data['data']['items'])) {
                return [
                    'posts' => [],
                    'end_cursor' => null,
                    'has_more' => false,
                ];
            }

            $posts = [];
            foreach ($data['data']['items'] as $post) {
                $posts[] = $this->formatPostData($post);
            }

            $nextCursor = $data['pagination_token'] ?? $data['data']['next_max_id'] ?? null;

            return [
                'posts' => $posts,
                'end_cursor' => $nextCursor,
                'has_more' => !empty($nextCursor),
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching Instagram posts for {$usernameOrIdOrUrl}: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatPostData(array $post): array
    {
        $imageVersions = $post['image_versions']['items'] ?? [];
        $processedImageVersions = $this->processImageVersions($imageVersions);

        $processedCarouselMedia = null;
        if (isset($post['carousel_media'])) {
            $processedCarouselMedia = $this->processCarouselMedia($post['carousel_media']);
        }

        return [
            'post_id' => $post['id'],
            'media_id' => $post['id'],
            'ig_id' => $post['fbid'] ?? $post['id'],
            'username' => $post['user']['username'],
            'shortcode' => $post['code'],
            'is_video' => $post['media_type'] == 2,
            'video_url' => $post['media_type'] == 2 ? ($post['video_versions'][0]['url'] ?? null) : null,
            'video_view_count' => $post['media_type'] == 2 ? ($post['play_count'] ?? $post['view_count'] ?? null) : null,
            'has_audio' => $post['media_type'] == 2 ? ($post['has_audio'] ?? null) : null,
            'display_url' => $imageVersions[0]['url'] ?? null,
            'height' => $imageVersions[0]['height'] ?? null,
            'width' => $imageVersions[0]['width'] ?? null,
            'caption_original' => $post['caption']['text'] ?? null,
            'caption' => $this->cleanCaption($post['caption']['text'] ?? null),
            'thumbnail_url' => end($imageVersions)['url'] ?? null,
            'likes_count' => $post['like_count'] ?? 0,
            'comments_count' => $post['comment_count'] ?? 0,
            'published_at' => date('Y-m-d H:i:s', $post['taken_at']),
            'media_type' => $post['media_type'],
            'image' => [
                'image_high' => $processedImageVersions['image_high'],
                'image_mid' => $processedImageVersions['image_mid']
            ],
            'images_carousel' => $processedCarouselMedia,
        ];
    }

    private function processImageVersions(array $imageVersions): array
    {
        if (empty($imageVersions)) {
            return [
                'image_high' => null,
                'image_mid' => null
            ];
        }

        usort($imageVersions, function ($a, $b) {
            return ($b['width'] * $b['height']) - ($a['width'] * $a['height']);
        });

        $imageHighUrl = $imageVersions[0]['url'] ?? null;
        $imageMidUrl = $this->findMediumResolutionUrl($imageVersions) ?? $imageHighUrl;

        return [
            'image_high' => $imageHighUrl,
            'image_mid' => $imageMidUrl
        ];
    }

    private function findMediumResolutionUrl(array $imageVersions): ?string
    {
        foreach ($imageVersions as $version) {
            if ($version['width'] >= 500 && $version['width'] <= 710) {
                return $version['url'];
            }
        }

        foreach ($imageVersions as $version) {
            if ($version['width'] > 710 && $version['width'] <= 1000) {
                return $version['url'];
            }
        }

        return null;
    }

    private function processCarouselMedia(array $carouselMedia): array
    {
        $processedMedia = [];
        foreach ($carouselMedia as $media) {
            $imageVersions = $media['image_versions']['items'] ?? [];
            $processedVersions = $this->processImageVersions($imageVersions);

            $item = [
                'media_id' => $media['id'],
                'media_type' => $media['media_type'],
                'display_url' => $imageVersions[0]['url'] ?? null,
                'height' => $imageVersions[0]['height'] ?? null,
                'width' => $imageVersions[0]['width'] ?? null,
                'thumbnail_url' => end($imageVersions)['url'] ?? null,
                'image' => [
                    'image_high' => $processedVersions['image_high'],
                    'image_mid' => $processedVersions['image_mid']
                ]
            ];

            if ($media['media_type'] == 2) {
                $item['video_url'] = $media['video_versions'][0]['url'] ?? null;
                $item['has_audio'] = $media['has_audio'] ?? null;
                $item['video_duration'] = $media['video_duration'] ?? null;
            }

            $processedMedia[] = $item;
        }

        return $processedMedia;
    }

    private function cleanCaption(?string $caption): ?string
    {
        if ($caption === null) {
            return null;
        }

        $withoutEmojis = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}]/u', '', $caption);
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $withoutEmojis);
    }
}
