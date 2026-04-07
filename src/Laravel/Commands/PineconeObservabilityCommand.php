<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Commands;

use Illuminate\Console\Command;

/**
 * Phase 12: print observability_v2 flags and cost table keys (no secrets).
 */
final class PineconeObservabilityCommand extends Command
{
    protected $signature = 'pinecone:observability';

    protected $description = 'Show Phase 12 observability_v2 (trace correlation, embedding/LLM events, cost estimates)';

    public function handle(): int
    {
        /** @var array<string, mixed> $v2 */
        $v2 = config('pinecone.observability_v2', []);
        if (! is_array($v2)) {
            $this->error('pinecone.observability_v2 must be an array.');

            return self::FAILURE;
        }

        $costs = $v2['costs'] ?? [];
        $emb = is_array($costs) && is_array($costs['embedding_usd_per_1m_tokens'] ?? null)
            ? $costs['embedding_usd_per_1m_tokens']
            : [];
        $chat = is_array($costs) && is_array($costs['chat_usd_per_1m_tokens'] ?? null)
            ? $costs['chat_usd_per_1m_tokens']
            : [];

        $rows = [
            ['observability_v2.enabled', (bool) ($v2['enabled'] ?? false) ? 'yes' : 'no'],
            ['embedding_events', (bool) ($v2['embedding_events'] ?? true) ? 'yes' : 'no'],
            ['llm_events', (bool) ($v2['llm_events'] ?? true) ? 'yes' : 'no'],
            ['embedding cost models', (string) count($emb)],
            ['chat cost models', (string) count($chat)],
        ];

        $this->table(['Setting', 'Value'], $rows);
        $this->line('');
        $this->line('Use VectorOperationTrace::begin() to set a trace id; PineconeHttpRequestFinished and embedding/LLM events include it when present.');
        $this->line('See doc/observability.md for full details.');

        return self::SUCCESS;
    }
}
