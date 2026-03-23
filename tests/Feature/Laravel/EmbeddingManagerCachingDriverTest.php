<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Embeddings\CachingEmbeddingDriver;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingManager;

final class EmbeddingManagerCachingDriverTest extends PineconeFeatureTestCase
{
    public function test_returns_caching_wrapper_when_cache_enabled(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => [
                    'enabled' => true,
                    'ttl' => 60,
                ],
            ],
        ]);

        $manager = $this->app->make(EmbeddingManager::class);
        $this->assertInstanceOf(CachingEmbeddingDriver::class, $manager->driver());
    }
}
