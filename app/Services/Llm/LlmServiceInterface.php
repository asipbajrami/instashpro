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
     * @param array $validImages Array of images with 'path' and 'media_id'
     * @param string|null $caption Post caption
     * @param string $group Structure output group (tech, car, general)
     */
    public function extractProducts(array $validImages, ?string $caption = null, string $group = 'tech'): array;

    /**
     * Classify a post into a category
     *
     * @param string|null $caption Post caption
     * @param string|null $base64Image Base64 encoded image
     * @param string $defaultCategory Default category if classification fails
     */
    public function classifyPostCategory(?string $caption = null, ?string $base64Image = null, string $defaultCategory = 'tech'): string;
}
