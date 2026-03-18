<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\CreateIndexRequest;
use Vectora\Pinecone\DTO\ServerlessIndexSpec;

final class PineconeIndexAdminTest extends TestCase
{
    public function test_create_index_posts_to_control_plane(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(201, [], '{}');
        $admin = PineconeTestFactories::indexAdmin($http);

        $admin->createIndex(new CreateIndexRequest(
            'my-index',
            1536,
            'cosine',
            new ServerlessIndexSpec('aws', 'us-east-1')
        ));

        $req = $http->requests[0];
        $this->assertStringContainsString('/indexes', (string) $req->getUri());
        $this->assertSame('POST', $req->getMethod());
    }

    public function test_describe_index_returns_status(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], json_encode([
            'name' => 'i',
            'status' => 'Ready',
        ]));
        $admin = PineconeTestFactories::indexAdmin($http);

        $d = $admin->describeIndex('i');

        $this->assertSame('Ready', $d->status);
        $this->assertSame('GET', $http->requests[0]->getMethod());
    }
}
