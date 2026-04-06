<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\VectorStore\LocalMemoryVectorStore;
use Vectora\Pinecone\Laravel\VectorStoreManager;

final class VectorStoreMemoryDriverTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'vector_store' => ['default' => 'MEMORY'],
        ], $app);
    }

    public function test_default_vector_store_contract_uses_memory_when_configured(): void
    {
        $store = $this->app->make(VectorStoreContract::class);
        $this->assertInstanceOf(LocalMemoryVectorStore::class, $store);
    }

    public function test_vector_store_manager_resolves_named_memory_driver(): void
    {
        $m = $this->app->make(VectorStoreManager::class);
        $this->assertInstanceOf(LocalMemoryVectorStore::class, $m->driver('memory'));
    }

    public function test_memory_driver_resolution_ignores_pinecone_index(): void
    {
        $m = $this->app->make(VectorStoreManager::class);
        $this->assertSame(
            $m->driver('memory', 'logical-index-a'),
            $m->driver('memory', 'logical-index-b'),
        );
    }
}
