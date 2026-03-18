<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * A single vector row for upsert.
 *
 * @param  array<float>  $values
 * @param  array<string, mixed>|null  $metadata
 */
final readonly class VectorRecord
{
    /**
     * @param  array<float>  $values
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $id,
        public array $values,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array{id: string, values: array<float>, metadata?: array<string, mixed>}
     */
    public function toApiArray(): array
    {
        $row = [
            'id' => $this->id,
            'values' => $this->values,
        ];
        if ($this->metadata !== null && $this->metadata !== []) {
            $row['metadata'] = $this->metadata;
        }

        return $row;
    }
}
