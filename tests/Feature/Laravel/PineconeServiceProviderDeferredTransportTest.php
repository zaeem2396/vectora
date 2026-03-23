<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class PineconeServiceProviderDeferredTransportTest extends PineconeFeatureTestCase
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

    public function test_set_transport_short_circuits_guzzle(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], '{"upsertedCount":1}');
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $factory = $this->app->make(PineconeClientFactory::class);
        $factory->vectorStore()->upsert(
            new UpsertVectorsRequest([
                new VectorRecord('a', [0.1]),
            ])
        );

        $this->assertNotEmpty($http->requests);
    }
}
