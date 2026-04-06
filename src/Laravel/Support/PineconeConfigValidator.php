<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Support;

/** Validates `config('pinecone')` shape for safe runtime values (no API key checks). */
final class PineconeConfigValidator
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function validate(array $config): void
    {
        $http = $config['http'] ?? [];
        if (! is_array($http)) {
            throw new \InvalidArgumentException('pinecone.http must be an array.');
        }

        $timeout = (float) ($http['timeout'] ?? 30);
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('pinecone.http.timeout must be positive.');
        }

        $connect = (float) ($http['connect_timeout'] ?? 10);
        if ($connect <= 0) {
            throw new \InvalidArgumentException('pinecone.http.connect_timeout must be positive.');
        }

        $retries = (int) ($http['retries'] ?? 4);
        if ($retries < 1) {
            throw new \InvalidArgumentException('pinecone.http.retries must be at least 1.');
        }

        $eloquent = $config['eloquent'] ?? [];
        if (is_array($eloquent)) {
            $sync = strtolower((string) ($eloquent['default_sync'] ?? 'queued'));
            if (! in_array($sync, ['sync', 'queued'], true)) {
                throw new \InvalidArgumentException('pinecone.eloquent.default_sync must be "sync" or "queued".');
            }
        }

        $qc = $config['query_cache'] ?? [];
        if (is_array($qc) && (bool) ($qc['enabled'] ?? false)) {
            $ttl = $qc['ttl'] ?? null;
            if ($ttl !== null && $ttl !== '' && (int) $ttl < 0) {
                throw new \InvalidArgumentException('pinecone.query_cache.ttl must be non-negative when set.');
            }
        }

        $metrics = $config['metrics'] ?? [];
        if ($metrics !== [] && ! is_array($metrics)) {
            throw new \InvalidArgumentException('pinecone.metrics must be an array.');
        }

        $vs = $config['vector_store'] ?? [];
        if (! is_array($vs)) {
            throw new \InvalidArgumentException('pinecone.vector_store must be an array.');
        }
        $allowed = ['pinecone', 'memory', 'sqlite', 'qdrant', 'weaviate', 'pgvector'];
        $def = strtolower((string) ($vs['default'] ?? 'pinecone'));
        if (! in_array($def, $allowed, true)) {
            throw new \InvalidArgumentException(
                'pinecone.vector_store.default must be one of: '.implode(', ', $allowed).'.'
            );
        }
        $drivers = $vs['drivers'] ?? [];
        if (! is_array($drivers)) {
            throw new \InvalidArgumentException('pinecone.vector_store.drivers must be an array.');
        }
        $pg = $drivers['pgvector'] ?? [];
        if (is_array($pg) && isset($pg['dimensions']) && is_numeric($pg['dimensions']) && (int) $pg['dimensions'] < 1) {
            throw new \InvalidArgumentException('pinecone.vector_store.drivers.pgvector.dimensions must be at least 1.');
        }
    }
}
