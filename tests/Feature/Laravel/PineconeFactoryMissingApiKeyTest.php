<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use InvalidArgumentException;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class PineconeFactoryMissingApiKeyTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => '',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_transport_requires_api_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->app->make(PineconeClientFactory::class)->transport();
    }
}
