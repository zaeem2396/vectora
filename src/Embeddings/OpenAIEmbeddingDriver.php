<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Embeddings;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Vectora\Pinecone\Core\Exception\EmbeddingException;
use Vectora\Pinecone\Core\Http\Json;

/**
 * OpenAI `/v1/embeddings` (batched embedMany when multiple inputs).
 */
final class OpenAIEmbeddingDriver extends AbstractEmbeddingDriver
{
    /** @var array<string, mixed>|null  Last OpenAI `usage` object from the embeddings API */
    private ?array $lastUsage = null;

    public function __construct(
        private readonly Client $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl = 'https://api.openai.com/v1',
        private readonly int $batchSize = 100,
    ) {
        if ($batchSize < 1) {
            throw new \InvalidArgumentException('batchSize must be at least 1.');
        }
    }

    /**
     * Token usage from the last successful `/v1/embeddings` response (Phase 12 observability).
     *
     * @return array<string, mixed>|null
     */
    public function lastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function embed(string $text): array
    {
        if ($text === '') {
            throw new \InvalidArgumentException('Cannot embed empty text.');
        }

        $vectors = $this->embedMany([$text]);

        return $vectors[0];
    }

    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }
        foreach ($texts as $t) {
            if (! is_string($t)) {
                throw new \InvalidArgumentException('embedMany expects a list of strings.');
            }
            if ($t === '') {
                throw new \InvalidArgumentException('Cannot embed empty text.');
            }
        }

        $all = [];
        foreach (array_chunk($texts, $this->batchSize) as $chunk) {
            $all = array_merge($all, $this->requestEmbeddings($chunk));
        }

        return $all;
    }

    /**
     * @param  list<string>  $inputs
     * @return list<list<float>>
     */
    private function requestEmbeddings(array $inputs): array
    {
        $uri = rtrim($this->baseUrl, '/').'/embeddings';
        $payload = Json::encode([
            'model' => $this->model,
            'input' => count($inputs) === 1 ? $inputs[0] : array_values($inputs),
        ]);

        $request = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($payload));

        try {
            $response = $this->http->send($request);
        } catch (GuzzleException $e) {
            throw new EmbeddingException('OpenAI embeddings request failed: '.$e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        if ($status >= 400) {
            throw new EmbeddingException(sprintf('OpenAI embeddings HTTP %d: %s', $status, $body !== '' ? $body : 'no body'));
        }

        /** @var array<string, mixed> $data */
        $data = Json::decodeObject($body);
        $this->lastUsage = isset($data['usage']) && is_array($data['usage']) ? $data['usage'] : null;
        if (! isset($data['data']) || ! is_array($data['data'])) {
            throw new EmbeddingException('OpenAI embeddings response missing data array.');
        }

        $indexed = [];
        foreach ($data['data'] as $row) {
            if (! is_array($row) || ! isset($row['embedding']) || ! is_array($row['embedding'])) {
                continue;
            }
            $idx = isset($row['index']) && is_int($row['index']) ? $row['index'] : count($indexed);
            $vec = [];
            foreach ($row['embedding'] as $v) {
                if (is_numeric($v)) {
                    $vec[] = (float) $v;
                }
            }
            $indexed[$idx] = $vec;
        }
        ksort($indexed);
        $ordered = array_values($indexed);
        if (count($ordered) !== count($inputs)) {
            throw new EmbeddingException('OpenAI embeddings returned unexpected vector count.');
        }

        return $ordered;
    }
}
