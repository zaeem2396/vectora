<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Laravel\CachingVectorStore;

final class PineconeQueryCacheBindingTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => 'ns'],
            ],
            'query_cache' => [
                'enabled' => true,
                'ttl' => 120,
            ],
        ], $app);
    }

    public function test_vector_store_uses_caching_wrapper_when_enabled(): void
    {
        $store = $this->app->make(VectorStoreContract::class);
        $this->assertInstanceOf(CachingVectorStore::class, $store);
    }
