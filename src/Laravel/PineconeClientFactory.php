<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\MessageInterface;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Core\Observability\ObservabilityHooks;
use Vectora\Pinecone\Core\Pinecone\PineconeIndexAdmin;
use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;
use Vectora\Pinecone\Laravel\Support\InteractsWithPineconeConfig;

class PineconeClientFactory
{
    use InteractsWithPineconeConfig;

    private ?PineconeHttpTransport $transport = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function pineconeConfig(): array
    {
        /** @var array<string, mixed> $c */
        $c = $this->app['config']->get('pinecone', []);

        return $c;
    }

    public function transport(): PineconeHttpTransport
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        $c = $this->pineconeConfig();
        $apiKey = (string) ($c['api_key'] ?? '');
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Pinecone api_key is not configured.');
        }

        $http = $c['http'] ?? [];
        $client = new Client([
            'timeout' => (float) ($http['timeout'] ?? 30),
            'connect_timeout' => (float) ($http['connect_timeout'] ?? 10),
            'http_errors' => false,
        ]);

        $retry = new RetryPolicy(
            maxAttempts: max(1, (int) ($http['retries'] ?? 4)),
            initialDelayMs: (int) ($http['retry_delay_ms'] ?? 250),
            maxDelayMs: (int) ($http['max_delay_ms'] ?? 10_000),
            respectRetryAfter: (bool) ($http['respect_retry_after'] ?? true),
        );

        $hooks = $this->composeHooks($c);

        $factory = new HttpFactory;
        $this->transport = new PineconeHttpTransport(
            $client,
            $factory,
            $factory,
            $apiKey,
            (string) ($c['api_version'] ?? '2025-10'),
            $retry,
            $hooks,
        );

        return $this->transport;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function composeHooks(array $c): ?ObservabilityHooks
    {
        $parts = array_values(array_filter([
            $this->makeLoggingHooks($c),
            $this->makeDebugHooks($c),
        ]));

        if ($parts === []) {
            return null;
        }

        return ObservabilityHooks::stack(...$parts);
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function makeLoggingHooks(array $c): ?ObservabilityHooks
    {
        $log = $c['logging'] ?? [];
        if (! (bool) ($log['enabled'] ?? false)) {
            return null;
        }

        $channel = isset($log['channel']) && is_string($log['channel']) ? $log['channel'] : null;

        return new ObservabilityHooks(
            beforeRequest: function ($request) use ($channel): void {
                $line = [
                    'uri' => (string) $request->getUri(),
                    'method' => $request->getMethod(),
                ];
                if ($channel !== null) {
                    Log::channel($channel)->debug('pinecone.request', $line);
                } else {
                    Log::debug('pinecone.request', $line);
                }
            },
            afterResponse: function ($request, $response) use ($channel): void {
                $line = [
                    'uri' => (string) $request->getUri(),
                    'status' => $response->getStatusCode(),
                ];
                if ($channel !== null) {
                    Log::channel($channel)->debug('pinecone.response', $line);
                } else {
                    Log::debug('pinecone.response', $line);
                }
            },
            onError: function ($request, $e) use ($channel): void {
                $line = [
                    'uri' => (string) $request->getUri(),
                    'error' => $e->getMessage(),
                ];
                if ($channel !== null) {
                    Log::channel($channel)->warning('pinecone.error', $line);
                } else {
                    Log::warning('pinecone.error', $line);
                }
            },
        );
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function makeDebugHooks(array $c): ?ObservabilityHooks
    {
        $dbg = $c['debug'] ?? [];
        if (! is_array($dbg) || ! (bool) ($dbg['enabled'] ?? false)) {
            return null;
        }

        $channel = isset($dbg['channel']) && is_string($dbg['channel']) && $dbg['channel'] !== ''
            ? $dbg['channel']
            : null;
        $max = max(64, (int) ($dbg['body_preview_max'] ?? 2048));

        $log = static function (string $message, array $context) use ($channel): void {
            if ($channel !== null) {
                Log::channel($channel)->debug($message, $context);
            } else {
                Log::debug($message, $context);
            }
        };

        return new ObservabilityHooks(
            beforeRequest: function ($request) use ($log, $max): void {
                $preview = self::previewMessageBody($request, $max);
                $log('pinecone.debug.request', [
                    'uri' => (string) $request->getUri(),
                    'method' => $request->getMethod(),
                    'body_preview' => $preview,
                ]);
            },
            afterResponse: function ($request, $response) use ($log, $max): void {
                $preview = self::previewMessageBody($response, $max);
                $log('pinecone.debug.response', [
                    'uri' => (string) $request->getUri(),
                    'status' => $response->getStatusCode(),
                    'body_preview' => $preview,
                ]);
            },
            onError: null,
        );
    }

    /** Read up to $max bytes for logging and rewind the stream so callers can still read the body. */
    private static function previewMessageBody(MessageInterface $message, int $max): string
    {
        $stream = $message->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $raw = $stream->getContents();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        if (strlen($raw) > $max) {
            return substr($raw, 0, $max).'…';
        }

        return $raw;
    }

    public function vectorStore(?string $index = null): VectorStoreContract
    {
        $c = $this->pineconeConfig();
        $conn = $this->indexConnection($c, $index);
        if ($conn['host'] === '') {
            throw new \InvalidArgumentException(sprintf('Pinecone host is not configured for index [%s].', $index ?? (string) ($c['default'] ?? 'default')));
        }

        $ns = $conn['namespace'] ?? '';
        $defaultNamespace = is_string($ns) && $ns !== '' ? $ns : null;

        return new PineconeVectorStore($this->transport(), $conn['host'], $defaultNamespace);
    }

    public function indexAdmin(): IndexAdminContract
    {
        $c = $this->pineconeConfig();
        $base = (string) (($c['control_plane'] ?? [])['url'] ?? 'https://api.pinecone.io');

        return new PineconeIndexAdmin($this->transport(), $base);
    }

    /** @internal Testing seam */
    public function setTransport(?PineconeHttpTransport $transport): void
    {
        $this->transport = $transport;
    }
}
