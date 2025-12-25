<?php

namespace App\Providers;

use App\Services\Llm\LlmServiceFactory;
use App\Services\Llm\LlmServiceInterface;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
