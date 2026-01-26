<?php

namespace App\Services\Llm;

use App\Models\StructureOutput;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenRouterService implements LlmServiceInterface
{
    public const MODEL_FLASH = 'google/gemini-2.5-flash-preview-09-2025';
    public const MODEL_FLASH_LITE = 'google/gemini-2.5-flash-lite-preview-09-2025';
    public const MODEL_MISTRAL_SMALL = 'mistralai/mistral-small-3.2-24b-instruct';

    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        // FIXED: Use config() exclusively
        $this->apiKey = config('services.openrouter.api_key', '');
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1/chat/completions');
        $this->model = config('services.openrouter.default_model', self::MODEL_FLASH);

        if (empty($this->apiKey)) {
            throw new Exception('OpenRouter API key not configured in services.openrouter.api_key');
        }
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function useLite(): self
    {
        return $this->setModel(self::MODEL_FLASH_LITE);
    }

    public function useFlash(): self
    {
        return $this->setModel(self::MODEL_FLASH);
    }

    /**
     * Extract products from images and caption using unified schema
     *
     * @param array $validImages Array of images with 'path' and 'media_id'
     * @param string|null $caption Post caption
     * @param string $group Structure output group (tech, car, general)
     */
    public function extractProducts(array $validImages, ?string $caption = null, string $group = 'tech'): array
    {
        if (empty($validImages)) {
            throw new Exception('No valid images provided');
        }

        $content = [];
        $imageCount = count($validImages);

        // Build dynamic source enum based on actual image count
        $sourceEnum = array_map(fn($i) => "image_{$i}", range(1, $imageCount));

        // Add caption/text prompt
        $promptText = "Analyze the following Instagram post images" . ($caption ? " and caption" : "") . ". Extract all products shown.\n\n";
        if ($caption) {
            $promptText .= "Caption: {$caption}\n\n";
        }
        $promptText .= "Images are labeled in order: " . implode(', ', $sourceEnum) . ".";

        $content[] = [
            'type' => 'text',
            'text' => $promptText
        ];

        // Add images in order
        $imageMap = [];
        foreach ($validImages as $index => $imageData) {
            $imagePath = $imageData['path'];
            $imageKey = 'image_' . ($index + 1);
            $imageMap[$imageKey] = $imageData['media_id'];

            $mimeType = Storage::disk('public')->mimeType($imagePath);
            $imageContent = base64_encode(Storage::disk('public')->get($imagePath));

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$imageContent}"
                ]
            ];
        }

        // Build schema dynamically from database using group
        $schema = StructureOutput::buildJsonSchemaForGroup($group, $sourceEnum);

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a product extraction expert. Analyze Instagram posts to identify products being sold. Mark has_products=true if the post contains products with identifiable BRAND and MODEL (price is optional). Extract comprehensive product details including all specs, features, prices, and conditions visible in images or caption. If no price is mentioned, use price=0 (means "contact for price"). Be thorough and accurate. IMPORTANT: Never use placeholder values like "Unknown", "N/A", "NA", "None", "Not Available", "Not Specified", or similar. Only include attributes where you can extract actual values from the images or caption. Leave attributes empty or omit them entirely if the information is not available. CRITICAL: For the "source" field, you MUST include ALL images where each product appears - include every image showing the product from any angle, detail shots, packaging, etc. Do not just return the main image. The first images (image_1, image_2) are often cover photos - include them if they show the same product.'
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'response_format' => $schema
        ];

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
            ])->timeout(120)->post($this->baseUrl, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new Exception('OpenRouter error: ' . json_encode($responseData['error']));
            }

            $jsonText = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$jsonText) {
                throw new Exception('Invalid response format from OpenRouter API');
            }

            $parsedContent = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse response JSON: ' . json_last_error_msg());
            }

            $parsedContent['image_map'] = $imageMap;

            // Add metadata for wide event logging (no logging here - caller logs)
            $parsedContent['_metadata'] = [
                'provider' => 'openrouter',
                'model' => $this->model,
                'duration_ms' => $durationMs,
                'images_count' => $imageCount,
                'category' => $group,
                'has_caption' => !empty($caption),
            ];

            return $parsedContent;
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            // Re-throw with metadata attached for caller to log
            throw new Exception(json_encode([
                'message' => $e->getMessage(),
                '_metadata' => [
                    'provider' => 'openrouter',
                    'model' => $this->model,
                    'duration_ms' => $durationMs,
                    'images_count' => $imageCount,
                    'category' => $group,
                    'has_caption' => !empty($caption),
                    'error_type' => get_class($e),
                ],
            ]));
        }
    }

    /**
     * Classify a post into a category using Gemini Flash Lite (vision model)
     * Categories are fetched dynamically from StructureOutputGroup table
     *
     * @param string|null $caption Post caption
     * @param string|null $base64Image Base64 encoded image for visual classification
     * @param string $defaultCategory Default category if classification fails
     * @return array{category: string, _metadata: array}
     */
    public function classifyPostCategory(?string $caption = null, ?string $base64Image = null, string $defaultCategory = 'tech'): array
    {
        $hasCaption = !empty(trim($caption ?? ''));
        $hasImage = !empty($base64Image);

        // Need at least caption or image
        if (!$hasCaption && !$hasImage) {
            return [
                'category' => $defaultCategory,
                '_metadata' => [
                    'provider' => 'openrouter',
                    'model' => self::MODEL_MISTRAL_SMALL,
                    'duration_ms' => 0,
                    'has_caption' => false,
                    'has_image' => false,
                    'fallback' => true,
                    'fallback_reason' => 'no_input',
                ],
            ];
        }

        // Fetch categories from database
        $groups = \App\Models\StructureOutputGroup::all();

        if ($groups->isEmpty()) {
            return [
                'category' => $defaultCategory,
                '_metadata' => [
                    'provider' => 'openrouter',
                    'model' => self::MODEL_MISTRAL_SMALL,
                    'duration_ms' => 0,
                    'has_caption' => $hasCaption,
                    'has_image' => $hasImage,
                    'fallback' => true,
                    'fallback_reason' => 'no_groups',
                ],
            ];
        }

        $categoryNames = $groups->pluck('used_for')->toArray();

        // Build prompt with dynamic categories from DB
        $promptText = "Classify this Instagram post into ONE category based on what products are being sold.\n\nCategories:\n";

        foreach ($groups as $group) {
            $shortDesc = mb_substr($group->description, 0, 150);
            $promptText .= "- {$group->used_for}: {$shortDesc}\n";
        }

        if ($hasCaption) {
            $promptText .= "\nCaption: " . $caption;
        }

        // Build user content - multimodal if image provided
        $userContent = [];

        $userContent[] = [
            'type' => 'text',
            'text' => $promptText
        ];

        if ($hasImage) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:image/jpeg;base64,{$base64Image}"
                ]
            ];
        }

        $schema = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'post_classification',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => [
                            'type' => 'string',
                            'enum' => $categoryNames,
                            'description' => 'The category that best matches the products in this post'
                        ]
                    ],
                    'required' => ['category'],
                    'additionalProperties' => false
                ]
            ]
        ];

        $payload = [
            'model' => self::MODEL_MISTRAL_SMALL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Find the best category for this post. Only return the category name, no other text or explanation.'
                ],
                [
                    'role' => 'user',
                    'content' => $userContent
                ]
            ],
            'response_format' => $schema
        ];

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
            ])->timeout(30)->post($this->baseUrl, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new Exception('OpenRouter error: ' . json_encode($responseData['error']));
            }

            $jsonText = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$jsonText) {
                return [
                    'category' => $defaultCategory,
                    '_metadata' => [
                        'provider' => 'openrouter',
                        'model' => self::MODEL_MISTRAL_SMALL,
                        'duration_ms' => $durationMs,
                        'has_caption' => $hasCaption,
                        'has_image' => $hasImage,
                        'fallback' => true,
                        'fallback_reason' => 'empty_response',
                    ],
                ];
            }

            $parsed = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'category' => $defaultCategory,
                    '_metadata' => [
                        'provider' => 'openrouter',
                        'model' => self::MODEL_MISTRAL_SMALL,
                        'duration_ms' => $durationMs,
                        'has_caption' => $hasCaption,
                        'has_image' => $hasImage,
                        'fallback' => true,
                        'fallback_reason' => 'json_parse_error',
                    ],
                ];
            }

            $category = $parsed['category'] ?? $defaultCategory;
            $isFallback = false;
            $fallbackReason = null;

            // Validate it's one of the allowed categories from DB
            if (!in_array($category, $categoryNames)) {
                $isFallback = true;
                $fallbackReason = 'invalid_category';
                $category = $defaultCategory;
            }

            return [
                'category' => $category,
                '_metadata' => [
                    'provider' => 'openrouter',
                    'model' => self::MODEL_MISTRAL_SMALL,
                    'duration_ms' => $durationMs,
                    'has_caption' => $hasCaption,
                    'has_image' => $hasImage,
                    'fallback' => $isFallback,
                    'fallback_reason' => $fallbackReason,
                ],
            ];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'category' => $defaultCategory,
                '_metadata' => [
                    'provider' => 'openrouter',
                    'model' => self::MODEL_MISTRAL_SMALL,
                    'duration_ms' => $durationMs,
                    'has_caption' => $hasCaption,
                    'has_image' => $hasImage,
                    'fallback' => true,
                    'fallback_reason' => 'exception',
                    'error_message' => $e->getMessage(),
                ],
            ];
        }
    }
}
