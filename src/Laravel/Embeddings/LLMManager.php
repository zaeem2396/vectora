<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;

/** Resolves configured {@see LLMDriver} implementations. */
final class LLMManager
{
    public function __construct(
        private readonly LLMDriverFactory $factory,
        private readonly Application $app,
        private readonly Dispatcher $events,
        private readonly ObservabilityCostEstimator $costs,
    ) {}

    public function driver(?string $name = null): LLMDriver
    {
        $inner = $this->factory->make($name);

        if (! $this->shouldObserveLlm()) {
            return $inner;
        }

        /** @var array<string, mixed> $llmCfg */
        $llmCfg = $this->app['config']->get('pinecone.llm', []);
        $raw = $name ?? (string) ($llmCfg['default'] ?? 'stub');
        $resolved = strtolower(trim($raw));
        if ($resolved === '') {
            $resolved = 'stub';
        }

        return new ObservedLlmDriver(
            $inner,
            $this->events,
            $this->costs,
            $resolved,
            $this->llmModelFor($resolved),
        );
    }

    private function shouldObserveLlm(): bool
    {
        /** @var array<string, mixed> $v2 */
        $v2 = $this->app['config']->get('pinecone.observability_v2', []);
        if (! is_array($v2)) {
            return false;
        }

        return (bool) ($v2['enabled'] ?? false) && (bool) ($v2['llm_events'] ?? true);
    }

    /**
     * @param  non-empty-string  $driverKey
     * @return non-empty-string
     */
    private function llmModelFor(string $driverKey): string
    {
        /** @var array<string, mixed> $llmCfg */
        $llmCfg = $this->app['config']->get('pinecone.llm', []);
        $drivers = $llmCfg['drivers'] ?? [];
        if (! is_array($drivers)) {
            return $driverKey;
        }
        $cfg = $drivers[$driverKey] ?? [];
        if (! is_array($cfg)) {
            return $driverKey;
        }

        $m = (string) ($cfg['model'] ?? $driverKey);

        return $m !== '' ? $m : $driverKey;
    }
}
