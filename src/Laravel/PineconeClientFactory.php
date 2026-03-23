<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Core\Observability\ObservabilityHooks;
use Vectora\Pinecone\Core\Pinecone\PineconeIndexAdmin;
use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;
use Vectora\Pinecone\Laravel\Support\InteractsWithPineconeConfig;

final class PineconeClientFactory
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

        $hooks = $this->makeHooks($c);

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
    private function makeHooks(array $c): ?ObservabilityHooks
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
