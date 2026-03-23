<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\EmbeddingDriver;

/** Resolves embedding drivers and optional cache decoration. */
final class EmbeddingManager
{
    public function __construct(
        private readonly Application $app,
        private readonly EmbeddingDriverFactory $factory,
    ) {}

    public function driver(?string $name = null): EmbeddingDriver
    {
        $inner = $this->factory->make($name);

        /** @var array<string, mixed> $embedCfg */
        $embedCfg = $this->app['config']->get('pinecone.embeddings', []);
        $cache = $embedCfg['cache'] ?? [];
        if (! is_array($cache)) {
            $cache = [];
        }

        if (! (bool) ($cache['enabled'] ?? false)) {
            return $inner;
        }

        $prefix = (string) ($cache['prefix'] ?? 'vectora.embeddings');
        $store = $cache['store'] ?? null;
        $storeName = is_string($store) && $store !== '' ? $store : null;

        $ttlRaw = $cache['ttl'] ?? null;
        $ttlSeconds = null;
        if ($ttlRaw !== null && $ttlRaw !== '') {
            $ttlSeconds = max(1, (int) $ttlRaw);
        }

        /** @var CacheFactory $cacheFactory */
        $cacheFactory = $this->app->make('cache');
        $repository = $storeName !== null ? $cacheFactory->store($storeName) : $cacheFactory->store();

        return new CachingEmbeddingDriver($inner, $repository, $prefix, $ttlSeconds);
    }
}
