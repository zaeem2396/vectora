<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

final class PineconeManagerAdminRequiresPineconeDriverTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'vector_store' => ['default' => 'MeMoRy'],
        ], $app);
    }

    public function test_admin_throws_when_default_vector_store_is_not_pinecone(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('vector_store.default');
        Pinecone::admin();
    }

    public function test_index_admin_contract_binding_throws_when_default_vector_store_is_not_pinecone(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('vector_store.default');
        $this->app->make(IndexAdminContract::class);
    }
}
