<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * @param  list<QueryVectorMatch>  $matches
 * @param  array<string, mixed>|null  $usage
 */
final readonly class QueryVectorsResult
{
    /**
     * @param  list<QueryVectorMatch>  $matches
     * @param  array<string, mixed>|null  $usage
     */
    public function __construct(
        public array $matches,
        public ?string $namespace = null,
        public ?array $usage = null,
    ) {}
}
