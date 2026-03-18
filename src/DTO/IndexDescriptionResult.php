<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * Normalized describe-index response (control plane).
 *
 * @param  array<string, mixed>  $raw
 */
final readonly class IndexDescriptionResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public string $status,
        public array $raw,
    ) {}
}
