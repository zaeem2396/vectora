<?php

declare(strict_types=1);

namespace Vectora\Pinecone\LLM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\Core\Exception\LlmException;
use Vectora\Pinecone\Core\Http\Json;

/** OpenAI `/v1/chat/completions` (and compatible servers). */
final class OpenAILLMDriver implements LLMDriver
{
    /** @var array<string, mixed>|null  Last `usage` object from chat/completions */
    private ?array $lastUsage = null;

    public function __construct(
        private readonly Client $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl = 'https://api.openai.com/v1',
        private readonly float $temperature = 0.2,
        private readonly ?int $maxTokens = null,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function lastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function chat(array $messages): string
    {
        $uri = rtrim($this->baseUrl, '/').'/chat/completions';
        $body = [
            'model' => $this->model,
            'messages' => array_values($messages),
            'temperature' => $this->temperature,
        ];
        if ($this->maxTokens !== null) {
            $body['max_tokens'] = $this->maxTokens;
        }

        $request = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(Json::encode($body)));

        try {
            $response = $this->http->send($request);
        } catch (GuzzleException $e) {
            throw new LlmException('OpenAI chat request failed: '.$e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $raw = $response->getBody()->getContents();
        if ($status >= 400) {
            throw new LlmException(sprintf('OpenAI chat HTTP %d: %s', $status, $raw !== '' ? $raw : 'no body'));
        }

        /** @var array<string, mixed> $data */
        $data = Json::decodeObject($raw);
        $this->lastUsage = isset($data['usage']) && is_array($data['usage']) ? $data['usage'] : null;
        $choices = $data['choices'] ?? [];
        if (! is_array($choices) || $choices === []) {
            throw new LlmException('OpenAI chat response missing choices.');
        }
        $first = $choices[0];
        if (! is_array($first)) {
            throw new LlmException('OpenAI chat invalid choice shape.');
        }
        $msg = $first['message'] ?? null;
        if (! is_array($msg) || ! isset($msg['content']) || ! is_string($msg['content'])) {
            throw new LlmException('OpenAI chat missing message content.');
        }

        return $msg['content'];
    }

    public function streamChat(array $messages): \Generator
    {
        $uri = rtrim($this->baseUrl, '/').'/chat/completions';
        $body = [
            'model' => $this->model,
            'messages' => array_values($messages),
            'temperature' => $this->temperature,
            'stream' => true,
        ];
        if ($this->maxTokens !== null) {
            $body['max_tokens'] = $this->maxTokens;
        }

        $request = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'text/event-stream')
            ->withBody($this->streamFactory->createStream(Json::encode($body)));

        try {
            $response = $this->http->send($request, ['stream' => true]);
        } catch (GuzzleException $e) {
            throw new LlmException('OpenAI chat stream request failed: '.$e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            $raw = $response->getBody()->getContents();
            throw new LlmException(sprintf('OpenAI chat stream HTTP %d: %s', $status, $raw !== '' ? $raw : 'no body'));
        }

        $stream = $response->getBody();
        $carry = '';
        while (! $stream->eof()) {
            $carry .= $stream->read(1024);
            [$carry, $deltas] = $this->consumeSseLines($carry);
            foreach ($deltas as $d) {
                yield $d;
            }
        }
        [$carry, $deltas] = $this->consumeSseLines($carry."\n");
        foreach ($deltas as $d) {
            yield $d;
        }
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function consumeSseLines(string $buffer): array
    {
        $carry = $buffer;
        $deltas = [];
        while (($pos = strpos($carry, "\n")) !== false) {
            $line = trim(substr($carry, 0, $pos));
            $carry = substr($carry, $pos + 1);
            if ($line === '' || str_starts_with($line, ':')) {
                continue;
            }
            if (! str_starts_with($line, 'data:')) {
                continue;
            }
            $payload = trim(substr($line, 5));
            if ($payload === '[DONE]') {
                return ['', $deltas];
            }
            /** @var array<string, mixed>|null $json */
            $json = json_decode($payload, true);
            if (! is_array($json)) {
                continue;
            }
            $delta = $this->extractStreamDelta($json);
            if ($delta !== '') {
                $deltas[] = $delta;
            }
        }

        return [$carry, $deltas];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractStreamDelta(array $json): string
    {
        $choices = $json['choices'] ?? [];
        if (! is_array($choices) || ! isset($choices[0]) || ! is_array($choices[0])) {
            return '';
        }
        $c0 = $choices[0];
        $d = $c0['delta'] ?? null;
        if (! is_array($d)) {
            return '';
        }
        $content = $d['content'] ?? null;

        return is_string($content) ? $content : '';
    }
}
