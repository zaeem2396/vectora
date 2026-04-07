<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Observability;

use Illuminate\Contracts\Foundation\Application;

/**
 * Phase 12: rough USD estimates from token counts using configurable per-million-token rates.
 */
final class ObservabilityCostEstimator
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @param  non-empty-string  $model
     */
    public function estimateEmbeddingUsd(string $model, ?int $totalTokens): ?float
    {
        if ($totalTokens === null || $totalTokens < 1) {
            return null;
        }

        /** @var array<string, mixed> $cfg */
        $cfg = $this->app['config']->get('pinecone.observability_v2', []);
        $costs = is_array($cfg['costs'] ?? null) ? $cfg['costs'] : [];
        $table = is_array($costs['embedding_usd_per_1m_tokens'] ?? null)
            ? $costs['embedding_usd_per_1m_tokens']
            : [];
        $rate = $table[$model] ?? $table[strtolower($model)] ?? null;
        if (! is_numeric($rate)) {
            return null;
        }

        return round(((float) $rate) * ($totalTokens / 1_000_000), 8);
    }

    /**
     * @param  non-empty-string  $model
     */
    public function estimateChatUsd(string $model, ?int $promptTokens, ?int $completionTokens): ?float
    {
        if ($promptTokens === null && $completionTokens === null) {
            return null;
        }

        /** @var array<string, mixed> $cfg */
        $cfg = $this->app['config']->get('pinecone.observability_v2', []);
        $costs = is_array($cfg['costs'] ?? null) ? $cfg['costs'] : [];
        $table = is_array($costs['chat_usd_per_1m_tokens'] ?? null)
            ? $costs['chat_usd_per_1m_tokens']
            : [];
        $rate = $table[$model] ?? $table[strtolower($model)] ?? null;
        if (! is_numeric($rate)) {
            return null;
        }

        $r = (float) $rate;
        $p = $promptTokens ?? 0;
        $c = $completionTokens ?? 0;
        if ($p + $c < 1) {
            return null;
        }

        return round($r * (($p + $c) / 1_000_000), 8);
    }
}
