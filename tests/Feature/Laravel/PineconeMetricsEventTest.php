<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;
use Vectora\Pinecone\Laravel\Observability\EventDispatchingPineconeMetrics;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class PineconeMetricsEventTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'metrics' => ['enabled' => true],
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_dispatches_pinecone_http_request_finished_on_success(): void
    {
        Event::fake([PineconeHttpRequestFinished::class]);

        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], Json::encode([
            'dimension' => 8,
            'totalVectorCount' => 0,
            'namespaces' => [],
        ]));

        $metrics = $this->app->make(EventDispatchingPineconeMetrics::class);
        $factory = $this->app->make(PineconeClientFactory::class);
        $factory->setTransport(PineconeTestFactories::transport($http, metrics: $metrics));

        $this->app->make(VectorStoreContract::class)->describeIndexStats();

        Event::assertDispatched(PineconeHttpRequestFinished::class, function (PineconeHttpRequestFinished $e): bool {
            return $e->httpStatus === 200
                && $e->failureClass === null
                && strlen($e->correlationId) === 16
                && $e->durationSeconds >= 0.0;
        });
    }
}
