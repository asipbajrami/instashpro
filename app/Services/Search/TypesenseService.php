<?php

namespace App\Services\Search;

use App\Services\EmbeddingService;
use Exception;
use Typesense\Client;

class TypesenseService
{
    public Client $client;
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;

        // FIXED: Use config() instead of hardcoded values
        $this->client = new Client([
            'api_key' => config('typesense.api_key'),
            'nodes' => [
                [
                    'host' => config('typesense.host'),
                    'port' => config('typesense.port'),
                    'protocol' => config('typesense.protocol'),
                    'path' => config('typesense.path'),
                ],
            ],
            'connection_timeout_seconds' => config('typesense.connection_timeout', 2),
            'healthcheck_interval_seconds' => config('typesense.healthcheck_interval', 30),
            'num_retries' => config('typesense.num_retries', 3),
            'retry_interval_seconds' => config('typesense.retry_interval', 1),
        ]);
    }

    /**
     * Search for images using text input
     */
    public function searchImagesByText(
        string $text,
        string $instagramPostId,
        int $limit = 10
    ): array {
        if (empty(trim($text))) {
            return [];
        }

        $embedding = $this->embeddingService->getClipTextEmbedding($text);

        if (!$embedding) {
            return [];
        }

        $searchParameters = [
            'searches' => [
                [
                    'collection' => 'instagram_media',
                    'q' => '*',
                    'limit' => $limit,
                    'filter_by' => "instagram_post_id:$instagramPostId",
                    'exclude_fields' => 'embedding_clip',
                    'vector_query' => "embedding_clip:([" . implode(',', $embedding) . "], k:$limit)",
                ]
            ]
        ];

        try {
            $searchResults = $this->client->multiSearch->perform($searchParameters);

            $results = [];
            if (!empty($searchResults['results'][0]['hits'])) {
                foreach ($searchResults['results'][0]['hits'] as $hit) {
                    $results[] = [
                        'id' => $hit['document']['id'],
                        'score' => $hit['vector_distance'] ?? null,
                        'shortcode' => $hit['document']['shortcode'] ?? null,
                        'type' => $hit['document']['type'] ?? null,
                        'media_id' => $hit['document']['media_id'] ?? null
                    ];
                }
            }

            return $results;
        } catch (Exception $e) {
            throw new Exception('Typesense image search failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform hybrid search on ProductAttributeValues
     */
    public function searchAttributeValues(
        string $query,
        int $productAttributeId,
        int $limit = 10,
        ?bool $isTemp = null
    ): array {
        $filterBy = "product_attribute_id:=$productAttributeId";

        if ($isTemp !== null) {
            $isTempStr = $isTemp ? 'true' : 'false';
            $filterBy = "product_attribute_id:=$productAttributeId && is_temp:=$isTempStr";
        }

        $embedding = $this->embeddingService->getTextEmbedding($query);

        if (!$embedding) {
            // Fall back to text-only search
            $searchParameters = [
                'q' => $query,
                'query_by' => 'ai_value',
                'filter_by' => $filterBy,
                'limit' => $limit,
                'prefix' => 'false',
                'sort_by' => '_text_match:desc,score:desc',
                'exclude_fields' => 'embedding_text'
            ];

            try {
                return $this->client->collections['product_attribute_values']
                    ->documents
                    ->search($searchParameters);
            } catch (Exception $e) {
                throw new Exception('Typesense search failed: ' . $e->getMessage());
            }
        }

        // Hybrid search with embedding
        $alpha = config('typesense.search.hybrid_alpha', 0.5);
        $searchParameters = [
            'searches' => [
                [
                    'collection' => 'product_attribute_values',
                    'q' => $query,
                    'query_by' => 'ai_value',
                    'filter_by' => $filterBy,
                    'limit' => $limit,
                    'prefix' => 'false',
                    'sort_by' => '_text_match:desc,score:desc',
                    'vector_query' => "embedding_text:([" . implode(',', $embedding) . "], k:$limit, alpha:$alpha)",
                    'exclude_fields' => 'embedding_text'
                ]
            ]
        ];

        try {
            $searchResults = $this->client->multiSearch->perform($searchParameters);
            return $searchResults['results'][0] ?? ['hits' => []];
        } catch (Exception $e) {
            throw new Exception('Typesense search failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform semantic search on Categories using Typesense's built-in embedding
     * Uses multilingual-e5-large model for semantic similarity
     */
    public function searchCategories(string $query, int $limit = 5): array
    {
        if (empty(trim($query))) {
            return ['hits' => []];
        }

        // Use Typesense's built-in embedding model for semantic search
        // This automatically embeds the query using ts/multilingual-e5-large
        $searchParameters = [
            'q' => $query,
            'query_by' => 'embedding_e5_small',
            'limit' => $limit,
            'exclude_fields' => 'embedding_e5_small,embedding_clip',
        ];

        try {
            return $this->client->collections['category']
                ->documents
                ->search($searchParameters);
        } catch (Exception $e) {
            throw new Exception('Typesense category search failed: ' . $e->getMessage());
        }
    }

    /**
     * Find the closest structure output group based on caption and/or image
     * Uses CLIP model for cross-modal search (text-to-image, image-to-text)
     *
     * @param string|null $caption Post caption for text search
     * @param string|null $base64Image Base64 encoded image for image search
     * @param string $defaultGroup Fallback group if no match found
     * @return string The used_for value of the best matching group
     */
    public function findClosestStructureOutputGroup(
        ?string $caption = null,
        ?string $base64Image = null,
        string $defaultGroup = 'general'
    ): string {
        $hasCaption = !empty(trim($caption ?? ''));
        $hasImage = !empty($base64Image);

        // If nothing provided, return default
        if (!$hasCaption && !$hasImage) {
            return $defaultGroup;
        }

        try {
            $groupScores = [];

            // Search with caption using CLIP text embedding
            if ($hasCaption) {
                // Truncate caption for CLIP model (max ~77 tokens)
                $truncatedCaption = mb_substr($caption, 0, 200);

                $textResults = $this->client->collections['structure_output_groups']->documents->search([
                    'q' => $truncatedCaption,
                    'query_by' => 'embedding_clip',
                    'limit' => 2,
                    'exclude_fields' => 'embedding_text,embedding_clip',
                    'prefix' => 'false',
                ]);

                foreach ($textResults['hits'] ?? [] as $hit) {
                    $usedFor = $hit['document']['used_for'] ?? null;
                    $distance = $hit['vector_distance'] ?? 1;
                    if ($usedFor) {
                        $groupScores[$usedFor] = ($groupScores[$usedFor] ?? 0) + (1 - $distance);
                    }
                }
            }

            // Search with image using CLIP image embedding
            if ($hasImage) {
                $imageResults = $this->client->collections['structure_output_groups']->documents->search([
                    'q' => '*',
                    'vector_query' => 'embedding_clip:([], image:data:image/jpeg;base64,' . $base64Image . ', k:2)',
                    'limit' => 2,
                    'exclude_fields' => 'embedding_text,embedding_clip',
                ]);

                foreach ($imageResults['hits'] ?? [] as $hit) {
                    $usedFor = $hit['document']['used_for'] ?? null;
                    $distance = $hit['vector_distance'] ?? 1;
                    if ($usedFor) {
                        // Weight image slightly higher than text
                        $groupScores[$usedFor] = ($groupScores[$usedFor] ?? 0) + (1 - $distance) * 1.2;
                    }
                }
            }

            if (empty($groupScores)) {
                return $defaultGroup;
            }

            // Get best scoring group
            arsort($groupScores);
            $bestGroup = array_key_first($groupScores);
            $bestScore = $groupScores[$bestGroup];

            // Threshold: CLIP has higher distances than text models
            // Single source: score > 0.66 (distance < 0.34)
            // Both sources: score > 1.3 (combined)
            $threshold = ($hasCaption && $hasImage) ? 1.3 : 0.66;

            if ($bestScore >= $threshold) {
                return $bestGroup;
            }

            return $defaultGroup;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Structure output group search failed: ' . $e->getMessage());
            return $defaultGroup;
        }
    }

    /**
     * Combine weighted results from multiple search sources
     */
    public function combineWeightedResults(
        array $textPrompts,
        array $clipPrompts,
        float $textWeight,
        float $clipWeight
    ): array {
        $data = [
            ['prompts' => $textPrompts, 'weight' => $textWeight],
            ['prompts' => $clipPrompts, 'weight' => $clipWeight]
        ];

        $occurrences = [];
        $weightedDistances = [];

        foreach ($data as $source) {
            $weight = $source['weight'];
            foreach ($source['prompts'] as $item) {
                $id = $item['id'];
                $occurrences[$id] = ($occurrences[$id] ?? 0) + 1;
                $weightedDistance = $item['distance'] * $weight;
                $weightedDistances[$id][] = $weightedDistance;
            }
        }

        $finalResults = [];
        foreach ($weightedDistances as $id => $distances) {
            if ($occurrences[$id] > 1) {
                $finalResults[] = [
                    'id' => $id,
                    'weighted_distance' => array_sum($distances)
                ];
            }
        }

        usort($finalResults, fn($a, $b) => $a['weighted_distance'] <=> $b['weighted_distance']);

        return $finalResults;
    }
}
