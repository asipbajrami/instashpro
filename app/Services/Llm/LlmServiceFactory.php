<?php

namespace App\Services\Llm;

use Exception;

class LlmServiceFactory
{
    /**
     * Create an LLM service instance based on configuration
     *
     * @throws Exception
     */
    public static function make(): LlmServiceInterface
    {
        $provider = config('services.llm.provider', 'openrouter');

        return match ($provider) {
            'openrouter' => new OpenRouterService(),
            'litellm' => new LiteLLMService(),
            default => throw new Exception("Unknown LLM provider: {$provider}"),
        };
    }
}
