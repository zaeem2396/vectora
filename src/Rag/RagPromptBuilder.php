<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Rag;

use Vectora\Pinecone\DTO\RagSourceChunk;

/**
 * Builds OpenAI-style chat messages: system prompt with injected context, optional history, user question.
 *
 * @phpstan-import-type ChatMessage from \Vectora\Pinecone\Contracts\LLMDriver
 */
final class RagPromptBuilder
{
    /**
     * @param  list<RagSourceChunk>  $chunks
     * @param  list<array{role: string, content: string}>  $priorMessages  Only `user` and `assistant` (matches {@see ConversationMemory}); `system` entries are skipped to avoid multiple system messages.
     * @return list<ChatMessage>
     */
    public function buildMessages(
        array $chunks,
        string $userQuestion,
        string $systemInstructions,
        array $priorMessages = [],
    ): array {
        $context = $this->formatChunks($chunks);
        $systemContent = trim($systemInstructions);
        if ($context !== '') {
            $systemContent .= ($systemContent !== '' ? "\n\n" : '')."Context:\n".$context;
        }

        /** @var list<ChatMessage> $out */
        $out = [];
        if ($systemContent !== '') {
            $out[] = ['role' => 'system', 'content' => $systemContent];
        }
        foreach ($priorMessages as $row) {
            if (! is_array($row)) {
                continue;
            }
            $role = isset($row['role']) && is_string($row['role']) ? $row['role'] : '';
            $content = isset($row['content']) && is_string($row['content']) ? $row['content'] : '';
            if ($role === '' || $content === '') {
                continue;
            }
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $out[] = ['role' => $role, 'content' => $content];
        }
        $out[] = ['role' => 'user', 'content' => $userQuestion];

        return $out;
    }

    /**
     * @param  list<RagSourceChunk>  $chunks
     */
    public function formatChunks(array $chunks): string
    {
        if ($chunks === []) {
            return '';
        }
        $lines = [];
        $i = 1;
        foreach ($chunks as $c) {
            $snippet = trim($c->text);
            if ($snippet === '') {
                continue;
            }
            $lines[] = sprintf('[%d] (id=%s, score=%.4f) %s', $i, $c->id, $c->score, $snippet);
            $i++;
        }

        return implode("\n\n", $lines);
    }
}
