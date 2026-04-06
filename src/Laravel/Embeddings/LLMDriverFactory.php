<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\LLM\OpenAILLMDriver;
use Vectora\Pinecone\LLM\StubLLMDriver;

/** Builds named LLM drivers from `config('pinecone.llm')`. */
final class LLMDriverFactory
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function make(?string $name = null): LLMDriver
    {
        /** @var array<string, mixed> $cfg */
        $cfg = $this->app['config']->get('pinecone.llm', []);
        $raw = $name ?? (string) ($cfg['default'] ?? 'stub');
        $resolved = strtolower(trim($raw));
        if ($resolved === '') {
            $resolved = 'stub';
        }
        $drivers = $cfg['drivers'] ?? [];
        if (! is_array($drivers)) {
            $drivers = [];
        }

        /** @var array<string, mixed> $driverCfg */
        $driverCfg = is_array($drivers[$resolved] ?? null) ? $drivers[$resolved] : [];

        return match ($resolved) {
            'openai' => $this->makeOpenAI($driverCfg),
            'stub' => $this->makeStub($driverCfg),
            default => throw new \InvalidArgumentException(sprintf('Unknown LLM driver [%s].', $resolved)),
        };
    }

    /**
     * @param  array<string, mixed>  $driverCfg
     */
    private function makeStub(array $driverCfg): StubLLMDriver
    {
        $prefix = (string) ($driverCfg['prefix'] ?? 'STUB: ');

        return new StubLLMDriver($prefix);
    }

    /**
     * @param  array<string, mixed>  $driverCfg
     */
    private function makeOpenAI(array $driverCfg): OpenAILLMDriver
    {
        $apiKey = (string) ($driverCfg['api_key'] ?? '');
        if ($apiKey === '') {
            throw new \InvalidArgumentException('OpenAI LLM api_key is not configured.');
        }

        $model = (string) ($driverCfg['model'] ?? 'gpt-4o-mini');
        $baseUrl = (string) ($driverCfg['base_url'] ?? 'https://api.openai.com/v1');
        $temperature = (float) ($driverCfg['temperature'] ?? 0.2);
        $maxRaw = $driverCfg['max_tokens'] ?? null;
        $maxTokens = $maxRaw !== null && $maxRaw !== '' ? max(1, (int) $maxRaw) : null;

        $httpCfg = $this->app['config']->get('pinecone.http', []);
        if (! is_array($httpCfg)) {
            $httpCfg = [];
        }

        $client = new Client([
            'timeout' => (float) ($httpCfg['timeout'] ?? 120),
            'connect_timeout' => (float) ($httpCfg['connect_timeout'] ?? 10),
            'http_errors' => false,
        ]);

        $factory = new HttpFactory;

        return new OpenAILLMDriver(
            $client,
            $factory,
            $factory,
            $apiKey,
            $model,
            $baseUrl,
            $temperature,
            $maxTokens,
        );
    }
}
