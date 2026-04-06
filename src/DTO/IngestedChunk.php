<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * One text segment produced during ingestion, before embedding.
 *
 * @param  array<string, mixed>  $metadata
 */
final readonly class IngestedChunk
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $text,
        public int $index,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $extra
     */
    public function withMetadata(array $extra): self
    {
        return new self($this->text, $this->index, array_merge($this->metadata, $extra));
    }
}
