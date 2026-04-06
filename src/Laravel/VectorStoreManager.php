<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\VectorStore\LocalMemoryVectorStore;
use Vectora\Pinecone\Core\VectorStore\PgVectorVectorStore;
use Vectora\Pinecone\Core\VectorStore\QdrantVectorStore;
use Vectora\Pinecone\Core\VectorStore\SqliteVectorStore;
use Vectora\Pinecone\Core\VectorStore\WeaviateVectorStore;

/** Resolves {@see VectorStoreContract} from `config('pinecone.vector_store')`. */
final class VectorStoreManager
{
    /** @var array<string, VectorStoreContract> */
    private array $resolved = [];

    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @param  class-string<Embeddable>|null  $modelClass
     */
    public function forModel(?string $modelClass, ?string $pineconeIndex = null): VectorStoreContract
    {
        $driver = null;
        if ($modelClass !== null && is_subclass_of($modelClass, Embeddable::class)) {
            $driver = $modelClass::vectorEmbeddingStoreDriver();
        }
        $index = $pineconeIndex;
        if ($index === null && $modelClass !== null && is_subclass_of($modelClass, Embeddable::class)) {
            $index = $modelClass::vectorEmbeddingIndex();
        }

        return $this->driver($driver, $index);
    }

    public function driver(?string $name = null, ?string $pineconeIndex = null): VectorStoreContract
    {
        /** @var array<string, mixed> $config */
        $config = $this->app['config']->get('pinecone', []);
        $vs = $config['vector_store'] ?? [];
        if (! is_array($vs)) {
            $vs = [];
        }
        $default = strtolower(trim((string) ($vs['default'] ?? 'pinecone')));
        $driver = $name !== null ? strtolower(trim($name)) : $default;
        if ($driver === '') {
            $driver = 'pinecone';
        }
        // Only Pinecone uses $pineconeIndex; other drivers share one resolved instance per driver.
        $key = $driver === 'pinecone'
            ? $driver.'|'.($pineconeIndex ?? '∅')
            : $driver;

        if (! isset($this->resolved[$key])) {
            $this->resolved[$key] = $this->wrapQueryCache(
                $this->createDriver($driver, $pineconeIndex, $config),
                $config,
                $driver
            );
        }

        return $this->resolved[$key];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createDriver(string $driver, ?string $pineconeIndex, array $config): VectorStoreContract
    {
        return match ($driver) {
            'pinecone' => $this->app->make(PineconeClientFactory::class)->vectorStore($pineconeIndex),
            'memory' => $this->app->make(LocalMemoryVectorStore::class),
            'sqlite' => $this->makeSqlite($config),
            'qdrant' => $this->makeQdrant($config),
            'weaviate' => $this->makeWeaviate($config),
            'pgvector' => $this->makePgVector($config),
            default => throw new \InvalidArgumentException(sprintf('Unknown vector_store driver [%s].', $driver)),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function wrapQueryCache(VectorStoreContract $inner, array $config, string $driver): VectorStoreContract
    {
        if ($driver === 'pinecone') {
            return $inner;
        }

        $qc = $config['query_cache'] ?? [];
        if (! is_array($qc) || ! (bool) ($qc['enabled'] ?? false)) {
            return $inner;
        }

        $storeName = $qc['store'] ?? null;
        $manager = $this->app->make('cache');
        $cache = $storeName !== null && is_string($storeName) && $storeName !== ''
            ? $manager->store($storeName)
            : $manager->store();
        if (! $cache instanceof CacheRepository) {
            throw new \RuntimeException('Pinecone query_cache requires a Laravel cache repository.');
        }

        $prefix = (string) ($qc['prefix'] ?? 'vectora.pinecone.query');
        $ttlRaw = $qc['ttl'] ?? null;
        $ttlSeconds = $ttlRaw === null || $ttlRaw === '' ? null : (int) $ttlRaw;
        $fingerprint = $driver.'|'.spl_object_id($inner);

        return new CachingVectorStore($inner, $cache, $prefix, $ttlSeconds, $fingerprint);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeSqlite(array $config): SqliteVectorStore
    {
        $vs = $config['vector_store'] ?? [];
        $drivers = is_array($vs) ? ($vs['drivers'] ?? []) : [];
        $sqlite = is_array($drivers) ? ($drivers['sqlite'] ?? []) : [];
        $path = isset($sqlite['database']) && is_string($sqlite['database']) ? $sqlite['database'] : ':memory:';
        $table = isset($sqlite['table']) && is_string($sqlite['table']) ? $sqlite['table'] : 'vectora_vectors';

        return SqliteVectorStore::open($path, $table);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeQdrant(array $config): QdrantVectorStore
    {
        $vs = $config['vector_store'] ?? [];
        $drivers = is_array($vs) ? ($vs['drivers'] ?? []) : [];
        $q = is_array($drivers) ? ($drivers['qdrant'] ?? []) : [];
        $url = isset($q['url']) && is_string($q['url']) ? rtrim($q['url'], '/') : 'http://127.0.0.1:6333';
        $collection = isset($q['collection']) && is_string($q['collection']) ? $q['collection'] : 'vectora';
        $apiKey = isset($q['api_key']) && is_string($q['api_key']) ? $q['api_key'] : null;
        $key = $apiKey !== null && $apiKey !== '' ? $apiKey : null;

        return new QdrantVectorStore(new Client, $url, $collection, $key);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeWeaviate(array $config): WeaviateVectorStore
    {
        $vs = $config['vector_store'] ?? [];
        $drivers = is_array($vs) ? ($vs['drivers'] ?? []) : [];
        $w = is_array($drivers) ? ($drivers['weaviate'] ?? []) : [];
        $url = isset($w['url']) && is_string($w['url']) ? rtrim($w['url'], '/') : 'http://127.0.0.1:8080';
        $class = isset($w['class']) && is_string($w['class']) ? $w['class'] : 'VectoraVector';
        $apiKey = isset($w['api_key']) && is_string($w['api_key']) ? $w['api_key'] : null;
        $key = $apiKey !== null && $apiKey !== '' ? $apiKey : null;

        return new WeaviateVectorStore(new Client, $url, $class, $key);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makePgVector(array $config): PgVectorVectorStore
    {
        $vs = $config['vector_store'] ?? [];
        $drivers = is_array($vs) ? ($vs['drivers'] ?? []) : [];
        $pg = is_array($drivers) ? ($drivers['pgvector'] ?? []) : [];
        $conn = isset($pg['connection']) && is_string($pg['connection']) && $pg['connection'] !== ''
            ? $pg['connection']
            : null;
        $table = isset($pg['table']) && is_string($pg['table']) ? $pg['table'] : 'vectora_vectors';
        $dim = isset($pg['dimensions']) && is_numeric($pg['dimensions']) ? (int) $pg['dimensions'] : 8;
        $ensure = (bool) ($pg['ensure_schema'] ?? false);
        $db = $this->app->make('db');
        $pdo = $conn !== null ? $db->connection($conn)->getPdo() : $db->connection()->getPdo();

        return new PgVectorVectorStore($pdo, $table, max(1, $dim), $ensure);
    }
}
