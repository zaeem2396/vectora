<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Pinecone / vector services. Full bindings ship in later phases.
 */
class PineconeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/pinecone.php', 'pinecone');

        $this->app->singleton('vectora.pinecone', function ($app) {
            return new PineconeManager($app['config']->get('pinecone', []));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/pinecone.php' => config_path('pinecone.php'),
            ], 'pinecone-config');
        }
    }
}
