<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;

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
        return $this->app->make(PineconeClientFactory::class)->indexAdmin();
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
