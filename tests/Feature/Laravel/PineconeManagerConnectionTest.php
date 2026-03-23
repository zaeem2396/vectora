<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;
use Vectora\Pinecone\Laravel\PineconeManager;

final class PineconeManagerConnectionTest extends PineconeFeatureTestCase
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

    public function test_connection_returns_vector_store(): void
    {
        /** @var PineconeManager $m */
        $m = $this->app->make('vectora.pinecone');
        $this->assertInstanceOf(PineconeVectorStore::class, $m->connection());
    }

    public function test_config_returns_array_or_key(): void
    {
        /** @var PineconeManager $m */
        $m = $this->app->make('vectora.pinecone');
        $this->assertIsArray($m->config());
        $this->assertSame('test-key', $m->config('api_key'));
    }
}
