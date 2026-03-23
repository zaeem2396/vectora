<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Support\ServiceProvider;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Laravel\Commands\PineconeFlushCommand;
use Vectora\Pinecone\Laravel\Commands\PineconeSyncCommand;

class PineconeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/pinecone.php', 'pinecone');

        $this->app->singleton(PineconeClientFactory::class, function ($app) {
            return new PineconeClientFactory($app);
        });

        $this->app->singleton('vectora.pinecone', function ($app) {
            return new PineconeManager($app);
        });

        $this->app->bind(VectorStoreContract::class, function ($app) {
            return $app->make(PineconeClientFactory::class)->vectorStore();
        });

        $this->app->bind(IndexAdminContract::class, function ($app) {
            return $app->make(PineconeClientFactory::class)->indexAdmin();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/pinecone.php' => config_path('pinecone.php'),
            ], 'pinecone-config');
        }

        $this->commands([
            PineconeFlushCommand::class,
            PineconeSyncCommand::class,
        ]);
    }
}
