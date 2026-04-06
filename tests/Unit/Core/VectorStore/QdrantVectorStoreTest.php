<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\VectorStore\QdrantVectorStore;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;

final class QdrantVectorStoreTest extends TestCase
{
    public function test_upsert_posts_points_endpoint(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"result":{"status":"completed"}}'),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);

        $store = new QdrantVectorStore($client, 'http://q.test', 'col', null);
        $store->upsert(new UpsertVectorsRequest([new VectorRecord('id1', [0.1, 0.2], null)], 'n1'));

        $this->assertCount(1, $container);
        $req = $container[0]['request'];
        $this->assertStringContainsString('/collections/col/points', (string) $req->getUri());
        $this->assertSame('POST', $req->getMethod());
        $body = Json::decodeObject((string) $req->getBody());
        $points = $body['points'] ?? [];
        $this->assertIsArray($points);
        $this->assertArrayHasKey(0, $points);
        $id = $points[0]['id'] ?? null;
        $this->assertIsString($id);
        $this->assertLessThanOrEqual(36, strlen($id));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $id
        );
    }
}
