<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\HttpFactory;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Core\Observability\ObservabilityHooks;
use Vectora\Pinecone\Core\Pinecone\PineconeIndexAdmin;
use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;

final class PineconeTestFactories
{
    public static function httpFactory(): HttpFactory
    {
        return new HttpFactory;
    }

    public static function transport(
        MockHttpClient $client,
        ?RetryPolicy $retry = null,
        ?ObservabilityHooks $hooks = null,
    ): PineconeHttpTransport {
        $f = self::httpFactory();

        return new PineconeHttpTransport(
            $client,
            $f,
            $f,
            'test-api-key',
            '2025-10',
            $retry ?? new RetryPolicy(maxAttempts: 5, initialDelayMs: 1, maxDelayMs: 10),
            $hooks
        );
    }

    public static function vectorStore(
        MockHttpClient $client,
        string $host = 'https://idx.test.pinecone.io',
        ?string $defaultNamespace = null,
    ): PineconeVectorStore {
        return new PineconeVectorStore(self::transport($client), $host, $defaultNamespace);
    }

    public static function indexAdmin(MockHttpClient $client, string $control = 'https://api.pinecone.io'): PineconeIndexAdmin
    {
        return new PineconeIndexAdmin(self::transport($client), $control);
    }
}
