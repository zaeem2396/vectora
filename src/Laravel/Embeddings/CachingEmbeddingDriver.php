<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Cache\Repository;
use Vectora\Pinecone\Contracts\EmbeddingDriver;

/** Wraps an {@see EmbeddingDriver} with per-text cache keys (SHA-256 of input). */
final class CachingEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(
        private readonly EmbeddingDriver $inner,
        private readonly Repository $cache,
        private readonly string $prefix,
        private readonly ?int $ttlSeconds,
    ) {}

    public function embed(string $text): array
    {
        if ($text === '') {
            throw new \InvalidArgumentException('Cannot embed empty text.');
        }

        $key = $this->cacheKey($text);

        if ($this->ttlSeconds === null) {
            /** @var list<float> */
            return $this->cache->rememberForever($key, fn (): array => $this->inner->embed($text));
        }

        /** @var list<float> */
        return $this->cache->remember($key, $this->ttlSeconds, fn (): array => $this->inner->embed($text));
    }

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        /** @var list<list<float>|null> $results */
        $results = array_fill(0, count($texts), null);
        $missIndices = [];
        $missTexts = [];

        foreach ($texts as $i => $t) {
            if (! is_string($t)) {
                throw new \InvalidArgumentException('embedMany expects a list of strings.');
            }
            if ($t === '') {
                throw new \InvalidArgumentException('Cannot embed empty text.');
            }

            $key = $this->cacheKey($t);
            if ($this->cache->has($key)) {
                $raw = $this->cache->get($key);
                $results[$i] = $this->normalizeVector($raw);
            } else {
                $missIndices[] = $i;
                $missTexts[] = $t;
            }
        }

        if ($missTexts !== []) {
            $vectors = $this->inner->embedMany($missTexts);
            foreach ($missIndices as $j => $origIdx) {
                $vec = $vectors[$j] ?? null;
                if (! is_array($vec)) {
                    throw new \RuntimeException('Inner embedding driver returned an invalid vector.');
                }
                $normalized = $this->normalizeVector($vec);
                $this->put($this->cacheKey($missTexts[$j]), $normalized);
                $results[$origIdx] = $normalized;
            }
        }

        /** @var list<list<float>> $out */
        $out = [];
        foreach ($results as $row) {
            if (! is_array($row)) {
                throw new \RuntimeException('Missing embedding result for index.');
            }
            $out[] = $row;
        }

        return $out;
    }

    private function cacheKey(string $text): string
    {
        return $this->prefix.':'.hash('sha256', $text);
    }

    /**
     * @return list<float>
     */
    private function normalizeVector(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            throw new \RuntimeException('Cached embedding value is invalid or empty.');
        }
        $out = [];
        foreach ($raw as $v) {
            if (! is_numeric($v)) {
                throw new \RuntimeException('Cached embedding value is not numeric.');
            }
            $out[] = (float) $v;
        }

        return $out;
    }

    /**
     * @param  list<float>  $vector
     */
    private function put(string $key, array $vector): void
    {
        if ($this->ttlSeconds === null) {
            $this->cache->forever($key, $vector);
        } else {
            $this->cache->put($key, $vector, $this->ttlSeconds);
        }
    }
}
