<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * One retrieved passage used as RAG context.
 *
 * @param  array<string, mixed>  $metadata
 */
final readonly class RagSourceChunk
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $text,
        public float $score,
        public array $metadata = [],
    ) {}
}
