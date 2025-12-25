<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram Scraper (RapidAPI)
    |--------------------------------------------------------------------------
    */

    'instagram_scraper' => [
        'api_key' => env('INSTAGRAM_SCRAPER_API_KEY'),
        'api_host' => env('INSTAGRAM_SCRAPER_API_HOST', 'instagram-scraper-api2.p.rapidapi.com'),
        'base_url' => env('INSTAGRAM_SCRAPER_BASE_URL', 'https://instagram-scraper-api2.p.rapidapi.com/v1/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Provider Selection
    |--------------------------------------------------------------------------
    | Choose which LLM provider to use: 'openrouter' or 'litellm'
    */

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openrouter'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter LLM API
    |--------------------------------------------------------------------------
    */

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/chat/completions'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'google/gemini-2.5-flash-preview-09-2025'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LiteLLM Service
    |--------------------------------------------------------------------------
    */

    'litellm' => [
        'api_key' => env('LITELLM_API_KEY', ''),
        'base_url' => env('LITELLM_BASE_URL', 'https://server.datafynow.ai/v1/chat/completions'),
        'default_model' => env('LITELLM_DEFAULT_MODEL', 'ollama/qwen3-vl:235b'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Service (LiteLLM)
    |--------------------------------------------------------------------------
    */

    'embedding' => [
        'enabled' => env('EMBEDDING_ENABLED', false),
        'url' => env('EMBEDDING_URL', env('LITELLM_EMBEDDING_URL')),
        'api_key' => env('EMBEDDING_API_KEY', env('LITELLM_API_KEY')),
        'text_model' => env('EMBEDDING_TEXT_MODEL', 'qwen3-embedding'),
        'image_model' => env('EMBEDDING_IMAGE_MODEL', 'siglip2-embedding'),
    ],

];
