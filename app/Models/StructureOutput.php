<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructureOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'type',
        'description',
        'used_for',
        'parent_key',
        'required',
        'enum_values',
        'product_attribute_id',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    /**
     * Build JSON Schema for OpenRouter API
     * Common fields are hardcoded, only attributes come from database
     *
     * @param string $group Group name (tech or car)
     * @param array $sourceEnum Dynamic source enum for images (e.g., ['image_1', 'image_2'])
     */
    public static function buildJsonSchemaForGroup(string $group, array $sourceEnum = []): array
    {
        // Default to 'tech' if group not found
        $usedFor = in_array($group, ['tech', 'car']) ? $group : 'tech';

        // Get group-specific config
        $config = self::getGroupConfig($usedFor);

        // Build attributes schema from database
        $attributesSchema = self::buildAttributesSchema($usedFor);

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'product_extraction',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'has_products' => [
                            'type' => 'boolean',
                            'description' => 'True if sellable products with an identifiable BRAND and MODEL are found. Price is optional - products without prices are still valid.'
                        ],
                        'post_type' => [
                            'type' => 'string',
                            'enum' => $config['product_types'],
                            'description' => 'Primary type of product in the post'
                        ],
                        'products' => [
                            'type' => 'array',
                            'description' => 'Array of products extracted. Include products that have identifiable BRAND and MODEL. Price is optional - use 0 if no price is mentioned.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'brand' => [
                                        'type' => 'string',
                                        'description' => 'Product manufacturer or brand name'
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                        'description' => 'Product model name'
                                    ],
                                    'type' => [
                                        'type' => 'string',
                                        'enum' => $config['product_types'],
                                        'description' => 'Specific product category'
                                    ],
                                    'categories' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'maxItems' => 2,
                                        'description' => 'E-commerce categories. Maximum 2 most relevant categories only.'
                                    ],
                                    'price' => [
                                        'type' => 'number',
                                        'description' => 'ORIGINAL/REGULAR price (the HIGHER value). Often shown crossed out when discounted. Use 0 if no price mentioned. When two prices exist, this is ALWAYS the higher one.'
                                    ],
                                    'discount_price' => [
                                        'type' => 'number',
                                        'description' => 'SALE/DISCOUNTED price (the LOWER value). MUST be lower than price field. Use 0 if no discount exists. When only one price is shown, put it in price and use 0 here.'
                                    ],
                                    'currency' => [
                                        'type' => 'string',
                                        'enum' => ['USD', 'EUR', 'GBP', 'ALL'],
                                        'description' => 'Price currency (ISO code: USD, EUR, GBP, LEK , ALL=Albanian Lek)'
                                    ],
                                    'condition' => [
                                        'type' => 'string',
                                        'enum' => $config['condition_enum'],
                                        'description' => 'Product condition state'
                                    ],
                                    'attributes' => $attributesSchema,
                                    'product_details' => [
                                        'type' => 'string',
                                        'description' => 'Additional details, features, or notable information in the language of the post'
                                    ],
                                    'source' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                            'enum' => $sourceEnum ?: ['image_1']
                                        ],
                                        'description' => 'Image references where this product appears'
                                    ],
                                    'confidence' => [
                                        'type' => 'string',
                                        'enum' => ['high', 'high-medium', 'medium', 'medium-low', 'low'],
                                        'description' => 'Extraction confidence level'
                                    ]
                                ],
                                'required' => ['brand', 'name', 'type', 'categories', 'price', 'discount_price', 'currency', 'condition', 'attributes', 'product_details', 'source', 'confidence'],
                                'additionalProperties' => false
                            ]
                        ]
                    ],
                    'required' => ['has_products', 'post_type', 'products'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * Get group-specific configuration (product types, condition enums)
     */
    private static function getGroupConfig(string $group): array
    {
        $configs = [
            'tech' => [
                'product_types' => ['phone', 'laptop', 'computer', 'monitor', 'tablet', 'smartwatch', 'headphones', 'camera', 'gaming_console', 'general_electronics'],
                'condition_enum' => ['new', 'used', 'refurbished', 'unknown'],
            ],
            'car' => [
                'product_types' => ['car', 'motorcycle', 'scooter', 'truck', 'van', 'suv', 'bicycle', 'electric_vehicle'],
                'condition_enum' => ['new', 'used', 'certified_pre_owned', 'salvage', 'unknown'],
            ],
        ];

        return $configs[$group] ?? $configs['tech'];
    }

    /**
     * Build attributes schema from database for a specific group
     * Queries by parent_key to get ALL attributes belonging to that group
     */
    private static function buildAttributesSchema(string $group): array
    {
        // Query by parent_key to get all attributes for this group
        // (phone, computer, display all have parent_key = 'tech')
        $attributes = self::where('parent_key', $group)->get();

        $properties = [];
        $required = [];

        foreach ($attributes as $attr) {
            // Skip if this key was already added (avoid duplicates)
            if (isset($properties[$attr->key])) {
                continue;
            }

            $properties[$attr->key] = [
                'type' => 'string',
                'description' => $attr->description
            ];

            if ($attr->required) {
                $required[] = $attr->key;
            }
        }

        // Ensure no duplicates in required array
        $required = array_values(array_unique($required));

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false
        ];
    }
}
