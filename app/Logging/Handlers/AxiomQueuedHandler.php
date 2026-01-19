<?php

namespace App\Logging\Handlers;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Batched Axiom handler - collects logs and sends them at end of request.
 * Better for production: reduces HTTP calls from N to 1 per request.
 */
class AxiomQueuedHandler extends AbstractProcessingHandler
{
    private string $apiToken;
    private string $dataset;
    private string $endpoint = 'https://api.axiom.co/v1/datasets';
    private static array $buffer = [];
    private static bool $shutdownRegistered = false;

    public function __construct(
        string $apiToken,
        string $dataset,
        Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->apiToken = $apiToken;
        $this->dataset = $dataset;

        if (!self::$shutdownRegistered) {
            register_shutdown_function([$this, 'flush']);
            self::$shutdownRegistered = true;
        }
    }

    protected function write(LogRecord $record): void
    {
        self::$buffer[] = [
            'handler' => $this,
            'event' => $this->buildWideEvent($record),
        ];
    }

    public function flush(): void
    {
        if (empty(self::$buffer)) {
            return;
        }

        $events = array_map(fn($item) => $item['event'], self::$buffer);

        try {
            Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->endpoint}/{$this->dataset}/ingest", $events);
        } catch (\Throwable $e) {
            // Silently fail - don't break the app for logging
        }

        self::$buffer = [];
    }

    private function buildWideEvent(LogRecord $record): array
    {
        $request = request();

        return [
            '_time' => $record->datetime->format('c'),
            'level' => $record->level->name,
            'message' => $record->message,
            'channel' => $record->channel,

            'request.method' => $request?->method(),
            'request.path' => $request?->path(),
            'request.url' => $request?->fullUrl(),
            'request.ip' => $request?->ip(),
            'request.user_agent' => $request?->userAgent(),
            'request.id' => $request?->header('X-Request-ID'),

            'user.id' => auth()->id(),
            'user.email' => auth()->user()?->email,

            'app.environment' => config('app.env'),
            'app.name' => config('app.name'),
            'service.name' => 'instashpro-backend',

            ...$this->flattenContext($record->context),
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
