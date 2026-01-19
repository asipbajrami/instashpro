<?php

namespace App\Providers;

use App\Logging\Handlers\AxiomQueuedHandler;
use App\Services\Llm\LlmServiceFactory;
use App\Services\Llm\LlmServiceInterface;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LlmServiceInterface::class, function () {
            return LlmServiceFactory::make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LogViewer::auth(function ($request) {
            // The custom LogViewerAuth middleware handles the password check.
            // This gate can return true to allow access once authenticated.
            return true;
        });

        // Flush Axiom logs after each queue job completes
        // This is needed because the batched handler uses shutdown functions
        // which don't fire between jobs in long-running queue workers
        $this->flushAxiomAfterQueueJobs();
    }

    /**
     * Register queue event listeners to flush Axiom logs after each job.
     */
    private function flushAxiomAfterQueueJobs(): void
    {
        $flushAxiom = function () {
            try {
                $logger = Log::driver('axiom-batched');
                if ($logger) {
                    $handlers = $logger->getHandlers();
                    foreach ($handlers as $handler) {
                        if ($handler instanceof AxiomQueuedHandler) {
                            $handler->flush();
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Silently fail - don't break the queue for logging
            }
        };

        Event::listen(JobProcessed::class, $flushAxiom);
        Event::listen(JobFailed::class, $flushAxiom);
    }
}
