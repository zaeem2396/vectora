<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\PineconeManager;

final class PineconeManagerNamedIndexTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'default' => 'docs',
            'indexes' => [
                'docs' => ['host' => 'https://docs.test', 'namespace' => 'v1'],
                'cache' => ['host' => 'https://cache.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_named_connection_uses_distinct_host(): void
    {
        /** @var PineconeManager $m */
        $m = $this->app->make('vectora.pinecone');
        $this->assertNotSame($m->connection('docs'), $m->connection('cache'));
    }
}
