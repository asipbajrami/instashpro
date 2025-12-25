<?php

namespace App\Services\Llm;

use App\Models\StructureOutput;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LiteLLMService implements LlmServiceInterface
{
    public const MODEL_MISTRAL_SMALL = 'ollama/ministral-3:14b';

    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.litellm.api_key', '');
        $this->baseUrl = config('services.litellm.base_url', 'https://server.datafynow.ai/v1/chat/completions');
        $this->model = config('services.litellm.default_model', self::MODEL_MISTRAL_SMALL);
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
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
                    'content' => 'You are a product extraction expert. Analyze Instagram posts to identify products being sold. Mark has_products=true if the post contains products with identifiable BRAND and MODEL (price is optional). Extract comprehensive product details including all specs, features, prices, and conditions visible in images or caption. If no price is mentioned, use price=0 (means "contact for price") Note:If a discount is mentioned, use the discount price field and the price as the old price usually shown crossed out. Be thorough and accurate. IMPORTANT: Never use placeholder values like "Unknown", "N/A", "NA", "None", "Not Available", "Not Specified", or similar. Only include attributes where you can extract actual values from the images or caption. Leave attributes empty or omit them entirely if the information is not available.'
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'response_format' => $schema
        ];

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Only add Authorization header if API key is set
            if (!empty($this->apiKey)) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->post($this->baseUrl, $payload);

            if ($response->failed()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new Exception('LiteLLM error: ' . json_encode($responseData['error']));
            }

            $jsonText = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$jsonText) {
                throw new Exception('Invalid response format from LiteLLM API');
            }

            $parsedContent = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse response JSON: ' . json_last_error_msg());
            }

            $parsedContent['image_map'] = $imageMap;

            return $parsedContent;
        } catch (Exception $e) {
            throw new Exception('Failed to extract products: ' . $e->getMessage());
        }
    }

    /**
     * Classify a post into a category
     * Categories are fetched dynamically from StructureOutputGroup table
     *
     * @param string|null $caption Post caption
     * @param string|null $base64Image Base64 encoded image for visual classification
     * @param string $defaultCategory Default category if classification fails
     */
    public function classifyPostCategory(?string $caption = null, ?string $base64Image = null, string $defaultCategory = 'tech'): string
    {
        $hasCaption = !empty(trim($caption ?? ''));
        $hasImage = !empty($base64Image);

        // Need at least caption or image
        if (!$hasCaption && !$hasImage) {
            return $defaultCategory;
        }

        // Fetch categories from database
        $groups = \App\Models\StructureOutputGroup::all();

        if ($groups->isEmpty()) {
            return $defaultCategory;
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
            'model' => $this->model,
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

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if (!empty($this->apiKey)) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl, $payload);

            if ($response->failed()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new Exception('LiteLLM error: ' . json_encode($responseData['error']));
            }

            $jsonText = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$jsonText) {
                return $defaultCategory;
            }

            $parsed = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $defaultCategory;
            }

            $category = $parsed['category'] ?? $defaultCategory;

            // Validate it's one of the allowed categories from DB
            if (!in_array($category, $categoryNames)) {
                return $defaultCategory;
            }

            return $category;
        } catch (Exception $e) {
            // Log error but don't fail - return default
            \Illuminate\Support\Facades\Log::warning('Post classification failed: ' . $e->getMessage());
            return $defaultCategory;
        }
    }

    /**
     * Simple chat completion
     *
     * @param string $prompt The user prompt
     * @param string|null $systemPrompt Optional system prompt
     */
    public function chat(string $prompt, ?string $systemPrompt = null): string
    {
        $messages = [];

        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages
        ];

        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if (!empty($this->apiKey)) {
                $headers['Authorization'] = "Bearer {$this->apiKey}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($this->baseUrl, $payload);

            if ($response->failed()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new Exception('LiteLLM error: ' . json_encode($responseData['error']));
            }

            return $responseData['choices'][0]['message']['content'] ?? '';
        } catch (Exception $e) {
            throw new Exception('Chat request failed: ' . $e->getMessage());
        }
    }
}
