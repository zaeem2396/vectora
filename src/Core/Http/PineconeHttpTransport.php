<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use Vectora\Pinecone\Contracts\PineconeMetrics;
use Vectora\Pinecone\Core\Exception\ApiException;
use Vectora\Pinecone\Core\Observability\ObservabilityHooks;

/**
 * PSR-18 transport: JSON POST/GET/DELETE with retries, 429 Retry-After, and hooks.
 */
final class PineconeHttpTransport
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $apiKey,
        private readonly string $apiVersion,
        private readonly RetryPolicy $retryPolicy,
        private readonly ?ObservabilityHooks $hooks = null,
        private readonly ?PineconeMetrics $metrics = null,
    ) {}

    /**
     * @param  array<string, mixed>  $body  Use [] for empty object where API expects {}
     */
    public function postJson(string $baseUrl, string $path, array $body): ResponseInterface
    {
        $payload = $body === [] ? '{}' : Json::encode($body);

        return $this->sendWithRetries(function () use ($baseUrl, $path, $payload): RequestInterface {
            $uri = rtrim($baseUrl, '/').$path;

            return $this->requestFactory->createRequest('POST', $uri)
                ->withHeader('Api-Key', $this->apiKey)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Pinecone-Api-Version', $this->apiVersion)
                ->withBody($this->streamFactory->createStream($payload));
        });
    }

    public function get(string $baseUrl, string $path): ResponseInterface
    {
        return $this->sendWithRetries(function () use ($baseUrl, $path): RequestInterface {
            $uri = rtrim($baseUrl, '/').$path;

            return $this->requestFactory->createRequest('GET', $uri)
                ->withHeader('Api-Key', $this->apiKey)
                ->withHeader('X-Pinecone-Api-Version', $this->apiVersion);
        });
    }

    public function delete(string $baseUrl, string $path): ResponseInterface
    {
        return $this->sendWithRetries(function () use ($baseUrl, $path): RequestInterface {
            $uri = rtrim($baseUrl, '/').$path;

            return $this->requestFactory->createRequest('DELETE', $uri)
                ->withHeader('Api-Key', $this->apiKey)
                ->withHeader('X-Pinecone-Api-Version', $this->apiVersion);
        });
    }

    /**
     * @param  callable(): RequestInterface  $requestFactory
     */
    private function sendWithRetries(callable $requestFactory): ResponseInterface
    {
        $started = microtime(true);
        $correlationId = bin2hex(random_bytes(8));
        $attempt = 0;
        $lastThrowable = null;

        while ($attempt < $this->retryPolicy->maxAttempts) {
            $attempt++;
            $request = $requestFactory();
            try {
                $this->hooks?->beforeRequest($request);
                $response = $this->http->sendRequest($request);
                $code = $response->getStatusCode();

                if ($this->shouldRetryStatus($code) && $attempt < $this->retryPolicy->maxAttempts) {
                    $this->sleepBackoff($attempt, $response);
                    $response->getBody()->close();

                    continue;
                }

                if ($code >= 400) {
                    $body = $response->getBody()->getContents();
                    $this->hooks?->onError($request, new ApiException(
                        sprintf('Pinecone API error HTTP %d', $code),
                        $code,
                        $body !== '' ? $body : null
                    ));
                    $this->metrics?->recordHttpOutcome(
                        $request,
                        microtime(true) - $started,
                        $code,
                        $correlationId
                    );
                    throw new ApiException(
                        $this->messageFromErrorBody($body, $code),
                        $code,
                        $body !== '' ? $body : null
                    );
                }

                $this->hooks?->afterResponse($request, $response);
                $this->metrics?->recordHttpOutcome(
                    $request,
                    microtime(true) - $started,
                    $code,
                    $correlationId
                );

                return $response;
            } catch (ApiException $e) {
                throw $e;
            } catch (Throwable $e) {
                $lastThrowable = $e;
                $this->hooks?->onError($request, $e);
                if ($attempt >= $this->retryPolicy->maxAttempts) {
                    $this->metrics?->recordTransportFailure(
                        $request,
                        microtime(true) - $started,
                        $e::class,
                        $correlationId
                    );
                    throw $e;
                }
                $delay = $this->retryPolicy->delayMsForAttempt($attempt, null);
                usleep($delay * 1000);
            }
        }

        if ($lastThrowable !== null) {
            throw $lastThrowable;
        }

        throw new \LogicException('Retry loop exhausted without response.');
    }

    private function shouldRetryStatus(int $code): bool
    {
        return $code === 429 || $code === 408 || ($code >= 500 && $code <= 599);
    }

    private function sleepBackoff(int $attempt, ResponseInterface $response): void
    {
        $retryAfter = null;
        if ($this->retryPolicy->respectRetryAfter && $response->hasHeader('Retry-After')) {
            $h = $response->getHeaderLine('Retry-After');
            if (is_numeric($h)) {
                $retryAfter = (int) $h;
            }
        }
        $ms = $this->retryPolicy->delayMsForAttempt($attempt, $retryAfter);
        usleep($ms * 1000);
    }

    private function messageFromErrorBody(string $body, int $code): string
    {
        if ($body === '') {
            return sprintf('Pinecone API error HTTP %d', $code);
        }
        try {
            /** @var array<string, mixed> $j */
            $j = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (isset($j['message']) && is_string($j['message'])) {
                return $j['message'];
            }
            if (isset($j['error']) && is_string($j['error'])) {
                return $j['error'];
            }
        } catch (\JsonException) {
        }

        return sprintf('Pinecone API error HTTP %d', $code);
    }

    public static function normalizeBaseUrl(string $host): string
    {
        $host = trim($host);
        if ($host === '') {
            throw new \InvalidArgumentException('Host/base URL must not be empty.');
        }
        if (! str_starts_with($host, 'http://') && ! str_starts_with($host, 'https://')) {
            $host = 'https://'.$host;
        }

        return rtrim($host, '/');
    }
}
