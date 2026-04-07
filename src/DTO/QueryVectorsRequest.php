<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * Semantic query against an index (single query vector).
 *
 * @param  array<float>  $vector
 * @param  array<string, mixed>|null  $filter  Pinecone metadata filter DSL
 */
final readonly class QueryVectorsRequest
{
    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>|null  $filter
     * @param  array{indices: list<int>, values: list<float>}|null  $sparseVector  Pinecone hybrid sparse component when index supports it
     */
    public function __construct(
        public array $vector,
        public int $topK,
        public ?string $namespace = null,
        public ?array $filter = null,
        public bool $includeMetadata = true,
        public bool $includeValues = false,
        public ?string $queryByVectorId = null,
        public ?array $sparseVector = null,
        public ?float $hybridAlpha = null,
        public ?string $paginationToken = null,
    ) {
        if ($topK < 1) {
            throw new \InvalidArgumentException('topK must be at least 1.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiBody(): array
    {
        $body = [
            'topK' => $this->topK,
            'includeMetadata' => $this->includeMetadata,
            'includeValues' => $this->includeValues,
        ];
        if ($this->queryByVectorId !== null) {
            $body['id'] = $this->queryByVectorId;
        } else {
            $body['vector'] = $this->vector;
        }
        if ($this->namespace !== null && $this->namespace !== '') {
            $body['namespace'] = $this->namespace;
        }
        if ($this->filter !== null && $this->filter !== []) {
            $body['filter'] = $this->filter;
        }
        if ($this->sparseVector !== null && $this->sparseVector !== []) {
            $body['sparseVector'] = $this->sparseVector;
        }
        if ($this->hybridAlpha !== null) {
            $body['alpha'] = $this->hybridAlpha;
        }
        if ($this->paginationToken !== null && $this->paginationToken !== '') {
            $body['paginationToken'] = $this->paginationToken;
        }

        return $body;
    }
}
