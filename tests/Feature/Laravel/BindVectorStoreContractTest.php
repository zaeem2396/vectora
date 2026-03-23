<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;

final class BindVectorStoreContractTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_resolves_pinecone_vector_store(): void
    {
        $store = $this->app->make(VectorStoreContract::class);
        $this->assertInstanceOf(PineconeVectorStore::class, $store);
    }
}
