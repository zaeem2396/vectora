<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class PineconeSyncCommandTest extends PineconeFeatureTestCase
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

    public function test_sync_describes_index(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], json_encode([
            'dimension' => 4,
            'totalVectorCount' => 2,
            'metric' => 'cosine',
            'namespaces' => ['' => ['vectorCount' => 2]],
        ]));
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $this->artisan('pinecone:sync')
            ->expectsOutputToContain('2')
            ->assertExitCode(0);
    }
}
