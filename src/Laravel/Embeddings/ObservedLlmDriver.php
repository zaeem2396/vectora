<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Illuminate\Contracts\Events\Dispatcher;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\Laravel\Events\LlmCallFinished;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;
use Vectora\Pinecone\LLM\OpenAILLMDriver;

/**
 * Phase 12: wraps an {@see LLMDriver} to dispatch {@see LlmCallFinished} for non-streaming chat.
 */
final class ObservedLlmDriver implements LLMDriver
{
    public function __construct(
        private readonly LLMDriver $inner,
        private readonly Dispatcher $events,
        private readonly ObservabilityCostEstimator $costs,
        private readonly string $driverName,
        private readonly string $chatModel,
    ) {}

    public function chat(array $messages): string
    {
        $t0 = microtime(true);
        try {
            return $this->inner->chat($messages);
        } finally {
            $this->dispatch($t0);
        }
    }

    public function streamChat(array $messages): \Generator
    {
        return $this->inner->streamChat($messages);
    }

    private function dispatch(float $startedAt): void
    {
        $duration = microtime(true) - $startedAt;
        $prompt = null;
        $completion = null;
        $total = null;
        if ($this->inner instanceof OpenAILLMDriver) {
            $u = $this->inner->lastUsage();
            if (is_array($u)) {
                if (isset($u['prompt_tokens']) && is_numeric($u['prompt_tokens'])) {
                    $prompt = (int) $u['prompt_tokens'];
                }
                if (isset($u['completion_tokens']) && is_numeric($u['completion_tokens'])) {
                    $completion = (int) $u['completion_tokens'];
                }
                if (isset($u['total_tokens']) && is_numeric($u['total_tokens'])) {
                    $total = (int) $u['total_tokens'];
                }
            }
        }

        $estimated = $this->costs->estimateChatUsd($this->chatModel, $prompt, $completion);

        $this->events->dispatch(new LlmCallFinished(
            VectorOperationTrace::current(),
            $this->driverName,
            $duration,
            $prompt,
            $completion,
            $total,
            $estimated,
        ));
    }
}
