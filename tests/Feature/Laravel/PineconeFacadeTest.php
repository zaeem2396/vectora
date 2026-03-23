<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

final class PineconeFacadeTest extends PineconeFeatureTestCase
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

    public function test_facade_resolves_connection(): void
    {
        $this->assertInstanceOf(PineconeVectorStore::class, Pinecone::connection());
    }
}
