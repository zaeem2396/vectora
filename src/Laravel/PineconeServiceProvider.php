<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Support\ServiceProvider;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\VectorStore\LocalMemoryVectorStore;
use Vectora\Pinecone\Laravel\Commands\PineconeFlushCommand;
use Vectora\Pinecone\Laravel\Commands\PineconeSyncCommand;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingDriverFactory;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingManager;
use Vectora\Pinecone\Laravel\Observability\EventDispatchingPineconeMetrics;
use Vectora\Pinecone\Laravel\Support\PineconeConfigValidator;

class PineconeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/pinecone.php', 'pinecone');

        $this->app->singleton(PineconeClientFactory::class, function ($app) {
            return new PineconeClientFactory($app);
        });

        $this->app->singleton(EventDispatchingPineconeMetrics::class, function ($app) {
            return new EventDispatchingPineconeMetrics($app->make('events'));
        });

        $this->app->singleton('vectora.pinecone', function ($app) {
            return new PineconeManager($app);
        });

        $this->app->singleton(LocalMemoryVectorStore::class);

        $this->app->singleton(VectorStoreManager::class);

        $this->app->bind(VectorStoreContract::class, function ($app) {
            return $app->make(VectorStoreManager::class)->driver();
        });

        $this->app->singleton(EmbeddingDriverFactory::class, function ($app) {
            return new EmbeddingDriverFactory($app);
        });

        $this->app->singleton(EmbeddingManager::class, function ($app) {
            return new EmbeddingManager($app, $app->make(EmbeddingDriverFactory::class));
        });

        $this->app->bind(EmbeddingDriver::class, function ($app) {
            return $app->make(EmbeddingManager::class)->driver();
        });

        $this->app->bind(IndexAdminContract::class, function ($app) {
            return $app->make(PineconeClientFactory::class)->indexAdmin();
        });
    }

    public function boot(): void
    {
        /** @var array<string, mixed> $pinecone */
        $pinecone = $this->app['config']->get('pinecone', []);
        PineconeConfigValidator::validate($pinecone);

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
