<?php

namespace App\Services\Llm;

interface LlmServiceInterface
{
    /**
     * Set the model to use
     */
    public function setModel(string $model): self;

    /**
     * Extract products from images and caption
     *
     * Returns array with extraction results and '_metadata' key for wide event logging.
     *
     * @param array $validImages Array of images with 'path' and 'media_id'
     * @param string|null $caption Post caption
     * @param string $group Structure output group (tech, car, general)
     * @return array{has_products: bool, products: array, image_map: array, _metadata: array{provider: string, model: string, duration_ms: int, images_count: int, category: string, has_caption: bool}}
     */
    public function extractProducts(array $validImages, ?string $caption = null, string $group = 'tech'): array;

    /**
     * Classify a post into a category
     *
     * Returns array with 'category' and '_metadata' keys for wide event logging.
     *
     * @param string|null $caption Post caption
     * @param string|null $base64Image Base64 encoded image
     * @param string $defaultCategory Default category if classification fails
     * @return array{category: string, _metadata: array{provider: string, model: string, duration_ms: int, has_caption: bool, has_image: bool, fallback: bool}}
     */
    public function classifyPostCategory(?string $caption = null, ?string $base64Image = null, string $defaultCategory = 'tech'): array;
}
