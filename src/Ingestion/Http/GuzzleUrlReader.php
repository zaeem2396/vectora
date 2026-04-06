<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/** Fetches URL bodies for web ingestion (Phase 9). */
final class GuzzleUrlReader
{
    public function __construct(
        private ?Client $client = null,
    ) {
        $this->client ??= new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'VectoraIngestion/1.0',
            ],
        ]);
    }

    /**
     * @throws \RuntimeException On HTTP errors or empty body
     */
    public function get(string $url): ResponseInterface
    {
        try {
            return $this->client->get($url);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('URL fetch failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function getBodyString(string $url): string
    {
        $response = $this->get($url);
        $raw = (string) $response->getBody();
        if ($raw === '') {
            throw new \RuntimeException(sprintf('Empty response body for [%s].', $url));
        }

        return $raw;
    }
}
