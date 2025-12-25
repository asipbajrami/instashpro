<?php

/*
|--------------------------------------------------------------------------
| Typesense Embedding Configuration
|--------------------------------------------------------------------------
|
| Using Typesense built-in GPU-accelerated embedding models.
| - ts/multilingual-e5-large: 1024 dimensions for text
| - ts/clip-vit-b-p32: 512 dimensions for images and cross-modal search
|
*/

// Text embedding config - Typesense built-in model
$textEmbeddingConfig = [
    'model_name' => 'ts/multilingual-e5-large',
];

// Image/CLIP embedding config - Typesense built-in model
$imageEmbeddingConfig = [
    'model_name' => 'ts/clip-vit-b-p32',
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    */

    'driver' => env('SCOUT_DRIVER', 'typesense'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => config('typesense.api_key', env('TYPESENSE_API_KEY', 'xyz')),
            'nodes' => [
                [
                    'host' => config('typesense.host', env('TYPESENSE_HOST', 'localhost')),
                    'port' => config('typesense.port', env('TYPESENSE_PORT', '8108')),
                    'path' => config('typesense.path', env('TYPESENSE_PATH', '')),
                    'protocol' => config('typesense.protocol', env('TYPESENSE_PROTOCOL', 'http')),
                ],
            ],
            'nearest_node' => [
                'host' => config('typesense.host', env('TYPESENSE_HOST', 'localhost')),
                'port' => config('typesense.port', env('TYPESENSE_PORT', '8108')),
                'path' => config('typesense.path', env('TYPESENSE_PATH', '')),
                'protocol' => config('typesense.protocol', env('TYPESENSE_PROTOCOL', 'http')),
            ],
            'connection_timeout_seconds' => config('typesense.connection_timeout', 2),
            'healthcheck_interval_seconds' => config('typesense.healthcheck_interval', 30),
            'num_retries' => config('typesense.num_retries', 3),
            'retry_interval_seconds' => config('typesense.retry_interval', 1),
        ],
        'model-settings' => [
            \App\Models\InstagramPost::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'caption', 'type' => 'string'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        [
                            'name' => 'embedding_caption',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['caption'],
                                'model_config' => $textEmbeddingConfig,
                            ]
                        ],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'embedding_caption'
                ],
            ],

            \App\Models\Category::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name', 'type' => 'string'],
                        ['name' => 'slug', 'type' => 'string'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        ['name' => 'score', 'type' => 'int32'],
                        ['name' => 'is_temp', 'type' => 'bool'],
                        [
                            'name' => 'embedding_e5_small',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['name'],
                                'model_config' => $textEmbeddingConfig,
                            ]
                        ],
                        [
                            'name' => 'embedding_clip',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['name'],
                                'model_config' => $imageEmbeddingConfig
                            ]
                        ]
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'embedding_clip'
                ],
            ],

            \App\Models\InstagramMedia::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'shortcode', 'type' => 'string'],
                        ['name' => 'type', 'type' => 'string'],
                        ['name' => 'instagram_post_id', 'type' => 'string'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        ['name' => 'used_for', 'type' => 'string'],
                        ['name' => 'media_id', 'type' => 'string'],
                        [
                            'name' => 'embedding_clip',
                            'type' => 'float[]',
                            'num_dim' => 768,
                            'optional' => true,
                        ]
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'embedding_clip'
                ],
            ],

            \App\Models\ProductAttributeValue::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'product_attribute_id', 'type' => 'string'],
                        ['name' => 'ai_value', 'type' => 'string'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        ['name' => 'is_temp', 'type' => 'bool'],
                        ['name' => 'score', 'type' => 'int32'],
                        [
                            'name' => 'embedding_text',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['ai_value'],
                                'model_config' => $textEmbeddingConfig,
                            ]
                        ],
                    ]
                ]
            ],

            \App\Models\StructureOutputGroup::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'used_for', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        [
                            'name' => 'embedding_text',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['description'],
                                'model_config' => $textEmbeddingConfig,
                            ]
                        ],
                        [
                            'name' => 'embedding_clip',
                            'type' => 'float[]',
                            'embed' => [
                                'from' => ['description'],
                                'model_config' => $imageEmbeddingConfig,
                            ]
                        ],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'embedding_clip'
                ],
            ],
        ],
    ],
];
