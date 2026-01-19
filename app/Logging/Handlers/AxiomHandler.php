<?php

namespace App\Logging\Handlers;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class AxiomHandler extends AbstractProcessingHandler
{
    private string $apiToken;
    private string $dataset;
    private string $endpoint = 'https://api.axiom.co/v1/datasets';

    public function __construct(
        string $apiToken,
        string $dataset,
        Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->apiToken = $apiToken;
        $this->dataset = $dataset;
    }

    protected function write(LogRecord $record): void
    {
        $event = $this->buildWideEvent($record);

        Http::withToken($this->apiToken)
            ->timeout(5)
            ->post("{$this->endpoint}/{$this->dataset}/ingest", [$event]);
    }

    private function buildWideEvent(LogRecord $record): array
    {
        $request = request();

        return [
            // Timestamp
            '_time' => $record->datetime->format('c'),

            // Log basics
            'level' => $record->level->name,
            'message' => $record->message,
            'channel' => $record->channel,

            // Request context (wide event data)
            'request.method' => $request?->method(),
            'request.path' => $request?->path(),
            'request.url' => $request?->fullUrl(),
            'request.ip' => $request?->ip(),
            'request.user_agent' => $request?->userAgent(),
            'request.id' => $request?->header('X-Request-ID'),

            // User context
            'user.id' => auth()->id(),
            'user.email' => auth()->user()?->email,

            // App context
            'app.environment' => config('app.env'),
            'app.name' => config('app.name'),
            'service.name' => 'instashpro-backend',

            // Extra context passed via Log::info('msg', [...])
            ...$this->flattenContext($record->context),

            // Additional record data
            ...$this->flattenContext($record->extra),
        ];
    }

    private function flattenContext(array $context, string $prefix = ''): array
    {
        $flat = [];

        foreach ($context as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenContext($value, $fullKey));
            } elseif (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $flat = array_merge($flat, $this->flattenContext($value->toArray(), $fullKey));
                } elseif (method_exists($value, '__toString')) {
                    $flat[$fullKey] = (string) $value;
                } else {
                    $flat[$fullKey] = get_class($value);
                }
            } else {
                $flat[$fullKey] = $value;
            }
        }

        return $flat;
    }
}
