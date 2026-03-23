<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Embeddings\DeterministicEmbeddingDriver;
use Vectora\Pinecone\Embeddings\OpenAIEmbeddingDriver;

/** Builds named embedding drivers from `config('pinecone.embeddings')`. */
final class EmbeddingDriverFactory
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function make(?string $name = null): EmbeddingDriver
    {
        /** @var array<string, mixed> $cfg */
        $cfg = $this->app['config']->get('pinecone.embeddings', []);
        $resolved = $name ?? (string) ($cfg['default'] ?? 'deterministic');
        $drivers = $cfg['drivers'] ?? [];
        if (! is_array($drivers)) {
            $drivers = [];
        }

        /** @var array<string, mixed> $driverCfg */
        $driverCfg = is_array($drivers[$resolved] ?? null) ? $drivers[$resolved] : [];

        return match ($resolved) {
            'openai' => $this->makeOpenAI($driverCfg),
            'deterministic' => $this->makeDeterministic($driverCfg),
            default => throw new \InvalidArgumentException(sprintf('Unknown embedding driver [%s].', $resolved)),
        };
    }

    /**
     * @param  array<string, mixed>  $driverCfg
     */
    private function makeDeterministic(array $driverCfg): DeterministicEmbeddingDriver
    {
        $dimensions = (int) ($driverCfg['dimensions'] ?? 8);

        return new DeterministicEmbeddingDriver($dimensions);
    }

    /**
     * @param  array<string, mixed>  $driverCfg
     */
    private function makeOpenAI(array $driverCfg): OpenAIEmbeddingDriver
    {
        $apiKey = (string) ($driverCfg['api_key'] ?? '');
        if ($apiKey === '') {
            throw new \InvalidArgumentException('OpenAI embedding api_key is not configured.');
        }

        $model = (string) ($driverCfg['model'] ?? 'text-embedding-3-small');
        $baseUrl = (string) ($driverCfg['base_url'] ?? 'https://api.openai.com/v1');
        $batchSize = max(1, (int) ($driverCfg['batch_size'] ?? 100));

        $httpCfg = $this->app['config']->get('pinecone.http', []);
        if (! is_array($httpCfg)) {
            $httpCfg = [];
        }

        $client = new Client([
            'timeout' => (float) ($httpCfg['timeout'] ?? 30),
            'connect_timeout' => (float) ($httpCfg['connect_timeout'] ?? 10),
            'http_errors' => false,
        ]);

        $factory = new HttpFactory;

        return new OpenAIEmbeddingDriver(
            $client,
            $factory,
            $factory,
            $apiKey,
            $model,
            $baseUrl,
            $batchSize,
        );
    }
}
