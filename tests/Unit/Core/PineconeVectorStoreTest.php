<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;

final class PineconeVectorStoreTest extends TestCase
{
    public function test_upsert_posts_expected_path_and_body(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], '{"upsertedCount":1}');
        $store = PineconeTestFactories::vectorStore($http);

        $store->upsert(new UpsertVectorsRequest([
            new VectorRecord('id1', [0.5, 0.5]),
        ], 'my-ns'));

        $this->assertCount(1, $http->requests);
        $req = $http->requests[0];
        $this->assertStringEndsWith('/vectors/upsert', (string) $req->getUri());
        $this->assertSame('POST', $req->getMethod());
        $body = json_decode((string) $req->getBody(), true);
        $this->assertSame('my-ns', $body['namespace']);
        $this->assertSame('id1', $body['vectors'][0]['id']);
    }

    public function test_query_parses_matches(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], json_encode([
            'matches' => [
                ['id' => 'm1', 'score' => 0.9, 'metadata' => ['t' => 1]],
            ],
            'namespace' => 'ns',
        ]));
        $store = PineconeTestFactories::vectorStore($http);

        $result = $store->query(new QueryVectorsRequest([0.1, 0.2], 5, 'ns'));

        $this->assertCount(1, $result->matches);
        $this->assertSame('m1', $result->matches[0]->id);
        $this->assertSame(0.9, $result->matches[0]->score);
        $this->assertStringEndsWith('/query', (string) $http->requests[0]->getUri());
    }

    public function test_describe_index_stats_empty_body_object(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], json_encode([
            'dimension' => 8,
            'totalVectorCount' => 2,
            'namespaces' => ['' => ['vectorCount' => 2]],
        ]));
        $store = PineconeTestFactories::vectorStore($http);

        $stats = $store->describeIndexStats();

        $this->assertSame(8, $stats->dimension);
        $this->assertSame(2, $stats->totalVectorCount);
        $raw = (string) $http->requests[0]->getBody();
        $this->assertSame('{}', $raw);
    }
}
