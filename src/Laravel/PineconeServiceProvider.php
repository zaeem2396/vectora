<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Support\ServiceProvider;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\VectorStore\LocalMemoryVectorStore;
use Vectora\Pinecone\Ingestion\ExtractorRegistry;
use Vectora\Pinecone\Ingestion\Http\GuzzleUrlReader;
use Vectora\Pinecone\Ingestion\IngestionPipeline;
use Vectora\Pinecone\Laravel\Commands\MakeVectorModelCommand;
use Vectora\Pinecone\Laravel\Commands\PineconeFlushCommand;
use Vectora\Pinecone\Laravel\Commands\PineconeSyncCommand;
use Vectora\Pinecone\Laravel\Commands\SemanticDebugCommand;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingDriverFactory;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingManager;
use Vectora\Pinecone\Laravel\Embeddings\LLMDriverFactory;
use Vectora\Pinecone\Laravel\Embeddings\LLMManager;
use Vectora\Pinecone\Laravel\Ingestion\IngestionFactory;
use Vectora\Pinecone\Laravel\Observability\EventDispatchingPineconeMetrics;
use Vectora\Pinecone\Laravel\Rag\RagQueryFactory;
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

        $this->app->singleton(LLMDriverFactory::class, function ($app) {
            return new LLMDriverFactory($app);
        });

        $this->app->singleton(LLMManager::class, function ($app) {
            return new LLMManager($app->make(LLMDriverFactory::class));
        });

        $this->app->bind(LLMDriver::class, function ($app) {
            return $app->make(LLMManager::class)->driver();
        });

        $this->app->singleton(ExtractorRegistry::class);

        $this->app->singleton(IngestionPipeline::class);

        $this->app->singleton(GuzzleUrlReader::class);

        $this->app->singleton(IngestionFactory::class);

        $this->app->singleton(RagQueryFactory::class, function ($app) {
            return new RagQueryFactory(
                $app->make(LLMManager::class),
                $app->make(IngestionFactory::class),
            );
        });

        $this->app->bind(IndexAdminContract::class, function ($app) {
            PineconeManager::assertDefaultVectorStoreDriverIsPinecone($app['config']->get('pinecone.vector_store', []));

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
            MakeVectorModelCommand::class,
            SemanticDebugCommand::class,
        ]);
    }
}
