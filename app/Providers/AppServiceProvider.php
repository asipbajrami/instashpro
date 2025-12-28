<?php

namespace App\Providers;

use App\Services\Llm\LlmServiceFactory;
use App\Services\Llm\LlmServiceInterface;
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
    }
}
