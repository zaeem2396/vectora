<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class BindPineconeClientFactoryTest extends PineconeFeatureTestCase
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

    public function test_factory_is_singleton(): void
    {
        $a = $this->app->make(PineconeClientFactory::class);
        $b = $this->app->make(PineconeClientFactory::class);
        $this->assertSame($a, $b);
    }
}
