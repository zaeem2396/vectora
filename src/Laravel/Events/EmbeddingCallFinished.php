<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Events;

use Vectora\Pinecone\Contracts\EmbeddingDriver;

/**
 * Phase 12: dispatched after {@see EmbeddingDriver::embed()} or {@see embedMany()}
 * when observability v2 embedding events are enabled.
 */
final class EmbeddingCallFinished
{
    /**
     * @param  non-empty-string  $driverName  Resolved embedding driver key (e.g. openai, deterministic)
     * @param  'embed'|'embed_many'  $operation
     */
    public function __construct(
        public readonly ?string $traceId,
        public readonly string $driverName,
        public readonly string $operation,
        public readonly float $durationSeconds,
        public readonly int $inputCharacters,
        public readonly int $batchSize,
        public readonly ?int $totalTokens,
        public readonly ?float $estimatedCostUsd,
    ) {}
}
