<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\Jobs\DescribeIndexStatsJob;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class DescribeIndexStatsJobHandlesTest extends PineconeFeatureTestCase
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

    public function test_dispatches_vector_synced_with_counts(): void
    {
        Event::fake([VectorSynced::class]);

        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], json_encode([
            'dimension' => 3,
            'totalVectorCount' => 9,
            'namespaces' => [],
        ]));
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $job = new DescribeIndexStatsJob(null);
        $job->handle($this->app->make(PineconeClientFactory::class));

        Event::assertDispatched(VectorSynced::class, function (VectorSynced $e): bool {
            return $e->operation === 'describe_index_stats'
                && ($e->context['totalVectorCount'] ?? 0) === 9;
        });
    }
}
