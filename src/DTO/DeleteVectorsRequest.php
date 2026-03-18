<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/**
 * Delete by ids, metadata filter, or entire namespace.
 *
 * @param  list<string>|null  $ids
 * @param  array<string, mixed>|null  $filter
 */
final readonly class DeleteVectorsRequest
{
    /**
     * @param  list<string>|null  $ids
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        public ?string $namespace = null,
        public ?array $ids = null,
        public ?array $filter = null,
        public bool $deleteAll = false,
    ) {
        $hasIds = $this->ids !== null && $this->ids !== [];
        $hasFilter = $filter !== null && $filter !== [];
        $modes = ($hasIds ? 1 : 0) + ($hasFilter ? 1 : 0) + ($deleteAll ? 1 : 0);
        if ($modes !== 1) {
            throw new \InvalidArgumentException(
                'Specify exactly one of: non-empty ids, filter, or deleteAll=true.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiBody(): array
    {
        $body = [];
        if ($this->namespace !== null && $this->namespace !== '') {
            $body['namespace'] = $this->namespace;
        }
        if ($this->deleteAll) {
            $body['deleteAll'] = true;
        } elseif ($this->filter !== null) {
            $body['filter'] = $this->filter;
        } else {
            $body['ids'] = array_values($this->ids ?? []);
        }

        return $body;
    }
}
