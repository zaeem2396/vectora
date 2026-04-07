<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Events\Dispatcher;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Embeddings\OpenAIEmbeddingDriver;
use Vectora\Pinecone\Laravel\Events\EmbeddingCallFinished;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

/**
 * Phase 12: wraps an {@see EmbeddingDriver} to dispatch {@see EmbeddingCallFinished} with duration and token usage.
 */
final class ObservedEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(
        private readonly EmbeddingDriver $inner,
        private readonly Dispatcher $events,
        private readonly ObservabilityCostEstimator $costs,
        private readonly string $driverName,
        private readonly string $embeddingModel,
    ) {}

    public function embed(string $text): array
    {
        $t0 = microtime(true);
        try {
            return $this->inner->embed($text);
        } finally {
            $this->dispatch(
                'embed',
                $t0,
                strlen($text),
                1,
            );
        }
    }

    public function embedMany(array $texts): array
    {
        $t0 = microtime(true);
        $chars = 0;
        foreach ($texts as $t) {
            $chars += strlen((string) $t);
        }
        try {
            return $this->inner->embedMany($texts);
        } finally {
            $this->dispatch(
                'embed_many',
                $t0,
                $chars,
                count($texts),
            );
        }
    }

    /**
     * @param  'embed'|'embed_many'  $operation
     */
    private function dispatch(string $operation, float $startedAt, int $inputCharacters, int $batchSize): void
    {
        $duration = microtime(true) - $startedAt;
        $openai = $this->unwrapOpenAI($this->inner);
        $totalTokens = null;
        if ($openai !== null) {
            $u = $openai->lastUsage();
            if (is_array($u) && isset($u['total_tokens']) && is_numeric($u['total_tokens'])) {
                $totalTokens = (int) $u['total_tokens'];
            }
        }

        $estimated = $this->costs->estimateEmbeddingUsd($this->embeddingModel, $totalTokens);

        $this->events->dispatch(new EmbeddingCallFinished(
            VectorOperationTrace::current(),
            $this->driverName,
            $operation,
            $duration,
            $inputCharacters,
            $batchSize,
            $totalTokens,
            $estimated,
        ));
    }

    private function unwrapOpenAI(EmbeddingDriver $d): ?OpenAIEmbeddingDriver
    {
        if ($d instanceof OpenAIEmbeddingDriver) {
            return $d;
        }
        if ($d instanceof CachingEmbeddingDriver) {
            $inner = $d->innerDriver();

            return $inner instanceof OpenAIEmbeddingDriver ? $inner : null;
        }

        return null;
    }
}
