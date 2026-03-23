<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Embeddings;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Embeddings\AbstractEmbeddingDriver;
use Vectora\Pinecone\Embeddings\DeterministicEmbeddingDriver;
use Vectora\Pinecone\Laravel\Embeddings\CachingEmbeddingDriver;

final class CachingEmbeddingDriverTest extends TestCase
{
    public function test_embed_uses_cache_on_second_call(): void
    {
        $inner = new DeterministicEmbeddingDriver(4);
        $repo = new Repository(new ArrayStore);
        $cache = new CachingEmbeddingDriver($inner, $repo, 't', 60);

        $a = $cache->embed('hello');
        $b = $cache->embed('hello');
        $this->assertSame($a, $b);
    }

    public function test_embed_many_hits_cache_for_known_strings(): void
    {
        $inner = new DeterministicEmbeddingDriver(4);
        $repo = new Repository(new ArrayStore);
        $cache = new CachingEmbeddingDriver($inner, $repo, 't', 60);

        $first = $cache->embedMany(['x', 'y']);
        $second = $cache->embedMany(['x', 'y']);
        $this->assertSame($first, $second);
    }

    public function test_embed_many_partial_misses_batch_inner(): void
    {
        $inner = new class extends AbstractEmbeddingDriver
        {
            public int $batchCalls = 0;

            public function embed(string $text): array
            {
                return [(float) crc32($text)];
            }

            public function embedMany(array $texts): array
            {
                $this->batchCalls++;

                return parent::embedMany($texts);
            }
        };
        $repo = new Repository(new ArrayStore);
        $cache = new CachingEmbeddingDriver($inner, $repo, 't', 60);

        $cache->embed('only-a');
        $cache->embedMany(['only-a', 'new-b']);

        $this->assertSame(1, $inner->batchCalls);
    }

    public function test_empty_embed_many_returns_empty(): void
    {
        $inner = new DeterministicEmbeddingDriver(4);
        $repo = new Repository(new ArrayStore);
        $cache = new CachingEmbeddingDriver($inner, $repo, 't', null);

        $this->assertSame([], $cache->embedMany([]));
    }
}
