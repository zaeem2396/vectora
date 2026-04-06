<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingManager;

/**
 * Laravel entry point: resolves named index connections and admin client.
 */
class PineconeManager
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function connection(?string $name = null): VectorStoreContract
    {
        return $this->app->make(PineconeClientFactory::class)->vectorStore($name);
    }

    public function admin(): IndexAdminContract
    {
        self::assertDefaultVectorStoreDriverIsPinecone($this->app['config']->get('pinecone.vector_store', []));

        return $this->app->make(PineconeClientFactory::class)->indexAdmin();
    }

    /**
     * Pinecone index admin (control plane) only applies when the default vector store is the Pinecone driver.
     *
     * @param  mixed  $vectorStoreConfig  `pinecone.vector_store` config array
     */
    public static function assertDefaultVectorStoreDriverIsPinecone(mixed $vectorStoreConfig): void
    {
        $vs = is_array($vectorStoreConfig) ? $vectorStoreConfig : [];
        $raw = isset($vs['default']) ? (string) $vs['default'] : 'pinecone';
        $default = strtolower(trim($raw));
        if ($default === '') {
            $default = 'pinecone';
        }
        if ($default !== 'pinecone') {
            throw new \RuntimeException(
                'Pinecone control plane (index admin) is only available when pinecone.vector_store.default is "pinecone".'
            );
        }
    }

    public function embeddings(?string $driver = null): EmbeddingDriver
    {
        return $this->app->make(EmbeddingManager::class)->driver($driver);
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        return $this->embeddings()->embed($text);
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embedMany(array $texts): array
    {
        return $this->embeddings()->embedMany($texts);
    }

    /**
     * @return array<string, mixed>
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $config */
        $config = $this->app['config']->get('pinecone', []);

        if ($key === null) {
            return $config;
        }

        return $config[$key] ?? $default;
    }
}
