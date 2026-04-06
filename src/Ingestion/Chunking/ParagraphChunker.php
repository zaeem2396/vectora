<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Chunking;

use Vectora\Pinecone\Contracts\TextChunkingStrategy;

/**
 * Splits on blank lines (double newline), then merges paragraphs into target size.
 */
final class ParagraphChunker implements TextChunkingStrategy
{
    public function __construct(
        private readonly int $maxParagraphsPerChunk = 3,
        private readonly int $maxCharsSoft = 2000,
    ) {
        if ($maxParagraphsPerChunk < 1) {
            throw new \InvalidArgumentException('maxParagraphsPerChunk must be at least 1.');
        }
    }

    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [];
        $paragraphs = array_values(array_filter(
            array_map(static fn (string $p): string => trim($p), $paragraphs),
            static fn (string $p): bool => $p !== ''
        ));

        if ($paragraphs === []) {
            return [];
        }

        $out = [];
        $buffer = '';
        $count = 0;
        foreach ($paragraphs as $p) {
            if ($count >= $this->maxParagraphsPerChunk
                || ($buffer !== '' && strlen($buffer) + strlen($p) + 2 > $this->maxCharsSoft)) {
                $out[] = trim($buffer);
                $buffer = $p;
                $count = 1;
            } else {
                $buffer = $buffer === '' ? $p : $buffer."\n\n".$p;
                $count++;
            }
        }
        if ($buffer !== '') {
            $out[] = trim($buffer);
        }

        return $out;
    }
}
