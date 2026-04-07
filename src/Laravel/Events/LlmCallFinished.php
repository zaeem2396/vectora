<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Events;

use Vectora\Pinecone\Contracts\LLMDriver;

/**
 * Phase 12: dispatched after a non-streaming {@see LLMDriver::chat()} call
 * when observability v2 LLM events are enabled.
 */
final class LlmCallFinished
{
    /**
     * @param  non-empty-string  $driverName
     */
    public function __construct(
        public readonly ?string $traceId,
        public readonly string $driverName,
        public readonly float $durationSeconds,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
        public readonly ?int $totalTokens,
        public readonly ?float $estimatedCostUsd,
    ) {}
}
