<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/** Create a serverless index via control plane API. */
final readonly class CreateIndexRequest
{
    public function __construct(
        public string $name,
        public int $dimension,
        public string $metric,
        public ServerlessIndexSpec $spec,
    ) {
        if ($dimension < 1) {
            throw new \InvalidArgumentException('dimension must be positive.');
        }
    }

    /**
     * @return array{name: string, dimension: int, metric: string, spec: array<string, mixed>}
     */
    public function toApiBody(): array
    {
        return [
            'name' => $this->name,
            'dimension' => $this->dimension,
            'metric' => $this->metric,
            'spec' => $this->spec->toSpecArray(),
        ];
    }
}
