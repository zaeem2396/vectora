<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Chunking;

use Vectora\Pinecone\Contracts\TextChunkingStrategy;

/**
 * Character-based windows with optional overlap (common RAG default).
 */
final class FixedSizeOverlappingChunker implements TextChunkingStrategy
{
    public function __construct(
        private readonly int $chunkSize,
        private readonly int $overlap = 0,
    ) {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException('chunkSize must be at least 1.');
        }
        if ($overlap < 0 || $overlap >= $chunkSize) {
            throw new \InvalidArgumentException('overlap must satisfy 0 <= overlap < chunkSize.');
        }
    }

    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $step = max(1, $this->chunkSize - $this->overlap);
        $out = [];
        $len = strlen($text);
        for ($i = 0; $i < $len; $i += $step) {
            $piece = substr($text, $i, $this->chunkSize);
            $piece = trim($piece);
            if ($piece !== '') {
                $out[] = $piece;
            }
            if ($i + $this->chunkSize >= $len) {
                break;
            }
        }

        return $out;
    }
}
