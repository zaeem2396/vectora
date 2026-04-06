<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

/**
 * Chat-style LLM completion (OpenAI-compatible message shape).
 *
 * @phpstan-type ChatMessage array{role: string, content: string}
 */
interface LLMDriver
{
    /**
     * Non-streaming completion.
     *
     * @param  list<ChatMessage>  $messages
     */
    public function chat(array $messages): string;

    /**
     * Token (or chunk) deltas; empty strings may be yielded and should be ignored by callers.
     *
     * @param  list<ChatMessage>  $messages
     * @return \Generator<int, string>
     */
    public function streamChat(array $messages): \Generator;
}
