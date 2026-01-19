---
name: logging
description: How to log in this Laravel project using Axiom wide events. Use when adding logging, debugging, or when the user asks about logs, observability, or monitoring.
---

# Logging with Axiom (Wide Events)

This project uses Axiom for structured logging following the "wide events" pattern from [loggingsucks.com](https://loggingsucks.com/).

## The Philosophy

**Stop logging what your code does. Start logging what happened to the request.**

Instead of 13-17 scattered log lines per request, emit ONE comprehensive event per service containing all debugging context.

## Configuration

- **Handler**: `app/Logging/Handlers/AxiomHandler.php` (sync) or `AxiomQueuedHandler.php` (batched)
- **Config**: `config/logging.php` → `axiom` and `axiom-batched` channels
- **Env vars**: `AXIOM_API_TOKEN`, `AXIOM_DATASET`, `LOG_STACK`

## Wide Event Structure

A proper wide event includes:

### Request Context (auto-included by handler)
```php
'request.method' => 'POST',
'request.path' => '/api/v1/orders',
'request.ip' => '192.168.1.1',
'request.user_agent' => '...',
'request.id' => 'uuid',
```

### User & Business Context (YOU add this)
```php
Log::info('Order completed', [
    // User context
    'user.id' => $user->id,
    'user.email' => $user->email,
    'user.tier' => $user->subscription_tier,      // Critical for debugging!
    'user.account_age_days' => $user->created_at->diffInDays(),

    // Business context
    'order.id' => $order->id,
    'order.total' => $order->total,
    'order.items_count' => $order->items->count(),
    'order.coupon_applied' => $order->coupon?->code,

    // Feature flags
    'flags.new_checkout' => $user->hasFeature('new_checkout'),
    'flags.beta_user' => $user->isBeta(),
]);
```

### Operational Context (for performance debugging)
```php
Log::info('Instagram profile scraped', [
    'profile.id' => $profile->id,
    'profile.username' => $profile->username,
    'profile.posts_count' => $postsCount,

    // Timing breakdown
    'duration.total_ms' => $totalMs,
    'duration.api_call_ms' => $apiMs,
    'duration.processing_ms' => $processingMs,

    // External service details
    'external.api_response_code' => $response->status(),
    'external.rate_limit_remaining' => $response->header('X-RateLimit-Remaining'),
]);
```

### Error Context (when things fail)
```php
Log::error('Payment failed', [
    'order.id' => $order->id,
    'order.total' => $order->total,

    'error.type' => get_class($e),
    'error.code' => $e->getCode(),
    'error.message' => $e->getMessage(),
    'error.retriable' => $this->isRetriable($e),

    'payment.provider' => 'stripe',
    'payment.attempt' => $attempt,
    'payment.method' => $paymentMethod->type,
]);
```

## Field Naming Conventions

Use dot notation consistently:

| Prefix | Usage |
|--------|-------|
| `user.*` | User context (id, email, tier, account_age) |
| `request.*` | HTTP request (auto-added) |
| `order.*` | Order/transaction data |
| `profile.*` | Instagram profile data |
| `post.*` | Instagram post data (id, shortcode) |
| `job.*` | Queue job context (type) |
| `run.*` | Processing run context (id) |
| `duration.*` | Timing breakdowns (total_ms, api_call_ms) |
| `external.*` | External API details |
| `error.*` | Error information |
| `flags.*` | Feature flags |
| `classification.*` | LLM classification results (category, model, provider, duration_ms) |
| `extraction.*` | LLM extraction results (model, provider, images_count, has_products) |
| `llm.*` | General LLM context when not classification/extraction |
| `skip.*` | Skip reason when operations are skipped |
| `products.*` | Product-related counts (created, skipped_low_confidence) |

## Services Return Metadata, Callers Log

**Critical pattern**: Services should NOT log internally. Instead, they return `_metadata` and let the caller (job/controller) emit ONE wide event.

### Bad: Service logs internally
```php
// DON'T - creates multiple log entries
class LlmService {
    public function extractProducts($images) {
        Log::info('LLM extraction started', [...]);  // Log 1
        $result = $this->callApi();
        Log::info('LLM extraction completed', [...]); // Log 2
        return $result;
    }
}
```

### Good: Service returns metadata
```php
// DO - return metadata for caller to log
class LlmService {
    public function extractProducts($images): array {
        $startTime = microtime(true);
        $result = $this->callApi();
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $result['_metadata'] = [
            'provider' => 'openrouter',
            'model' => $this->model,
            'duration_ms' => $durationMs,
            'images_count' => count($images),
        ];
        return $result;
    }
}
```

### Caller aggregates and logs once
```php
// In the job - ONE wide event with all context
class ProcessInstagramPost {
    public function handle() {
        $classificationResult = $this->llmService->classifyPost(...);
        $extractionResult = $this->llmService->extractProducts(...);

        Log::info('Post processed', [
            'job.type' => 'process_post',
            'post.id' => $this->postId,

            // Classification metadata
            'classification.category' => $classificationResult['category'],
            'classification.model' => $classificationResult['_metadata']['model'],
            'classification.duration_ms' => $classificationResult['_metadata']['duration_ms'],

            // Extraction metadata
            'extraction.model' => $extractionResult['_metadata']['model'],
            'extraction.duration_ms' => $extractionResult['_metadata']['duration_ms'],

            'duration.total_ms' => $totalDurationMs,
        ]);
    }
}
```

## Queue Job Wide Events

Jobs should emit ONE event at completion with full context:

```php
Log::info('Post processed', [
    // Job context (always include)
    'job.type' => 'process_post',
    'post.id' => $this->postId,
    'post.shortcode' => $post->shortcode,
    'run.id' => $this->runId,

    // Result
    'status' => 'success',  // or 'skipped', 'failed'
    'skip.reason' => null,  // e.g., 'No products detected'
    'products.created' => 3,

    // Classification details
    'classification.category' => 'tech',
    'classification.model' => 'mistralai/mistral-small-3.2-24b-instruct',
    'classification.provider' => 'openrouter',
    'classification.duration_ms' => 3386,
    'classification.fallback' => false,

    // Extraction details
    'extraction.model' => 'google/gemini-2.5-flash-preview-09-2025',
    'extraction.provider' => 'openrouter',
    'extraction.images_count' => 2,
    'extraction.has_products' => true,
    'extraction.duration_ms' => 3230,
    'extraction.attempts' => 1,

    // Timing
    'duration.total_ms' => 6661,
]);
```

## Anti-Patterns to Avoid

### Bad: Vague messages
```php
// DON'T
Log::info('Something happened');
Log::error('Error occurred');
```

### Bad: Missing business context
```php
// DON'T - useless for debugging
Log::info('Order placed');

// DO - queryable and debuggable
Log::info('Order placed', [
    'order.id' => $order->id,
    'order.total' => $order->total,
    'user.tier' => $user->tier,
]);
```

### Bad: Logging inside loops
```php
// DON'T - creates noise
foreach ($items as $item) {
    Log::info('Processing item', ['id' => $item->id]);
}

// DO - one event with summary
Log::info('Batch processed', [
    'batch.items_count' => count($items),
    'batch.success_count' => $successCount,
    'batch.failed_ids' => $failedIds,
]);
```

### Bad: Relying on auto-instrumentation alone
OpenTelemetry won't add your business context. YOU must explicitly add subscription tier, feature flags, order amounts, etc.

### Bad: Services logging internally
```php
// DON'T - services should return metadata, not log
class PaymentService {
    public function charge($amount) {
        Log::info('Payment started', ['amount' => $amount]);
        $result = $this->gateway->charge($amount);
        Log::info('Payment completed', ['success' => $result->success]);
        return $result;
    }
}

// DO - return _metadata for the caller to log
class PaymentService {
    public function charge($amount): array {
        $start = microtime(true);
        $result = $this->gateway->charge($amount);
        return [
            'success' => $result->success,
            '_metadata' => [
                'provider' => 'stripe',
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ],
        ];
    }
}
```

## Log Levels

| Level | When to Use |
|-------|-------------|
| `debug` | Development only, verbose tracing |
| `info` | Normal operations (order placed, user action, job completed) |
| `warning` | Unusual but handled (rate limit hit, retry triggered, fallback used) |
| `error` | Failures requiring attention (payment failed, external API error) |
| `critical` | System-level failures (database down, queue dead) |

## Querying in Axiom

The power of wide events is queryability:

```apl
// Find checkout failures for premium users with new checkout
| where level == "ERROR"
    and message contains "checkout"
    and user.tier == "premium"
    and flags.new_checkout == true

// P95 latency by user tier
| summarize percentile(duration.total_ms, 95) by user.tier

// Error rate by payment provider
| where message contains "payment"
| summarize
    total = count(),
    errors = countif(level == "ERROR")
  by payment.provider
| extend error_rate = errors * 100.0 / total

// Find slow Instagram scrapes
| where message contains "scraped" and duration.total_ms > 5000
| project profile.username, duration.total_ms, external.rate_limit_remaining

// Post processing stats by category
| where job.type == "process_post"
| summarize
    total = count(),
    processed = countif(status == "success"),
    skipped = countif(status == "skipped"),
    failed = countif(status == "failed")
  by classification.category

// LLM performance by model
| where job.type == "process_post"
| summarize
    avg_classification_ms = avg(classification.duration_ms),
    avg_extraction_ms = avg(extraction.duration_ms),
    p95_total_ms = percentile(duration.total_ms, 95)
  by extraction.model

// Skip reasons breakdown
| where job.type == "process_post" and status == "skipped"
| summarize count() by skip.reason

// Find posts with extraction retries
| where job.type == "process_post" and extraction.attempts > 1
| project post.shortcode, extraction.attempts, extraction.model, duration.total_ms
```

## Tail Sampling (for production costs)

Keep 100%:
- All errors (5xx, exceptions)
- Requests over p99 latency
- VIP/enterprise users
- Feature flag experiments

Sample 1-5%:
- Successful, fast requests

This is configured in the Axiom dashboard, not in code.

## Testing

```bash
php artisan tinker --execute="Log::info('Test wide event', [
    'user.id' => 1,
    'user.tier' => 'premium',
    'test.source' => 'manual',
]);"
```

Then query in Axiom: `| where test.source == "manual"`
