<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Laravel\Embeddings\EmbeddingManager;

final class EmbeddingCacheIntegrationTest extends PineconeFeatureTestCase
{
    public function test_cache_wraps_default_driver_when_enabled(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => [
                    'enabled' => true,
                    'prefix' => 'test.embed',
                    'ttl' => 120,
                ],
            ],
        ]);

        /** @var EmbeddingManager $manager */
        $manager = $this->app->make(EmbeddingManager::class);
        $driver = $manager->driver();
        $driver->embed('cached-once');

        $driver2 = $this->app->make(EmbeddingDriver::class);
        $driver2->embed('cached-once');

        $this->assertSame($driver->embed('cached-once'), $driver2->embed('cached-once'));
    }
}
