<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Embeddings;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Vectora\Pinecone\Core\Exception\EmbeddingException;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Embeddings\OpenAIEmbeddingDriver;

final class OpenAIEmbeddingDriverTest extends TestCase
{
    public function test_embed_single_parses_response(): void
    {
        $body = Json::encode([
            'data' => [
                ['index' => 0, 'embedding' => [0.25, -0.5]],
            ],
        ]);
        $client = $this->clientWithResponses([new Response(200, [], $body)]);
        $f = new HttpFactory;
        $d = new OpenAIEmbeddingDriver($client, $f, $f, 'sk-test', 'text-embedding-3-small');

        $v = $d->embed('hi');
        $this->assertSame([0.25, -0.5], $v);
    }

    public function test_embed_many_batches_and_merges(): void
    {
        $chunk1 = Json::encode([
            'data' => [
                ['index' => 0, 'embedding' => [1.0]],
                ['index' => 1, 'embedding' => [2.0]],
            ],
        ]);
        $chunk2 = Json::encode([
            'data' => [
                ['index' => 0, 'embedding' => [3.0]],
            ],
        ]);
        $client = $this->clientWithResponses([
            new Response(200, [], $chunk1),
            new Response(200, [], $chunk2),
        ]);
        $f = new HttpFactory;
        $d = new OpenAIEmbeddingDriver($client, $f, $f, 'k', 'm', batchSize: 2);

        $out = $d->embedMany(['a', 'b', 'c']);
        $this->assertSame([[1.0], [2.0], [3.0]], $out);
    }

    public function test_http_error_throws_embedding_exception(): void
    {
        $client = $this->clientWithResponses([new Response(401, [], 'nope')]);
        $f = new HttpFactory;
        $d = new OpenAIEmbeddingDriver($client, $f, $f, 'k', 'm');

        $this->expectException(EmbeddingException::class);
        $d->embed('x');
    }

    public function test_empty_string_throws(): void
    {
        $client = $this->clientWithResponses([]);
        $f = new HttpFactory;
        $d = new OpenAIEmbeddingDriver($client, $f, $f, 'k', 'm');

        $this->expectException(\InvalidArgumentException::class);
        $d->embed('');
    }

    public function test_embed_many_empty_returns_empty_without_http(): void
    {
        $client = $this->clientWithResponses([]);
        $f = new HttpFactory;
        $d = new OpenAIEmbeddingDriver($client, $f, $f, 'k', 'm');

        $this->assertSame([], $d->embedMany([]));
    }

    /**
     * @param  list<ResponseInterface>  $responses
     */
    private function clientWithResponses(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);

        return new Client(['handler' => $stack]);
    }
}
