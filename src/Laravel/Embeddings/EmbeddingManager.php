<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;

/** Resolves embedding drivers and optional cache decoration. */
final class EmbeddingManager
{
    public function __construct(
        private readonly Application $app,
        private readonly EmbeddingDriverFactory $factory,
        private readonly Dispatcher $events,
        private readonly ObservabilityCostEstimator $costs,
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

        $driver = $inner;
        if ((bool) ($cache['enabled'] ?? false)) {
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

            $driver = new CachingEmbeddingDriver($inner, $repository, $prefix, $ttlSeconds);
        }

        if ($this->shouldObserveEmbedding()) {
            $resolved = $name ?? (string) ($embedCfg['default'] ?? 'deterministic');
            $resolved = strtolower(trim($resolved));
            if ($resolved === '') {
                $resolved = 'deterministic';
            }

            return new ObservedEmbeddingDriver(
                $driver,
                $this->events,
                $this->costs,
                $resolved,
                $this->embeddingModelFor($resolved),
            );
        }

        return $driver;
    }

    private function shouldObserveEmbedding(): bool
    {
        /** @var array<string, mixed> $v2 */
        $v2 = $this->app['config']->get('pinecone.observability_v2', []);
        if (! is_array($v2)) {
            return false;
        }

        return (bool) ($v2['enabled'] ?? false) && (bool) ($v2['embedding_events'] ?? true);
    }

    /**
     * @param  non-empty-string  $driverKey
     * @return non-empty-string
     */
    private function embeddingModelFor(string $driverKey): string
    {
        /** @var array<string, mixed> $embedCfg */
        $embedCfg = $this->app['config']->get('pinecone.embeddings', []);
        $drivers = $embedCfg['drivers'] ?? [];
        if (! is_array($drivers)) {
            throw new \InvalidArgumentException('pinecone.embeddings.drivers must be an array.');
        }
        $cfg = $drivers[$driverKey] ?? [];
        if (! is_array($cfg)) {
            return $driverKey;
        }

        $m = (string) ($cfg['model'] ?? $driverKey);

        return $m !== '' ? $m : $driverKey;
    }
}
