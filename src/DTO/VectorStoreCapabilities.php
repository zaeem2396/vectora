<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

use Vectora\Pinecone\Contracts\VectorStoreContract;

/** Declared features of a {@see VectorStoreContract} implementation. */
final readonly class VectorStoreCapabilities
{
    public function __construct(
        public string $backendName,
        public bool $supportsNamespaces = true,
        public bool $supportsMetadataFilter = true,
        public bool $supportsDeleteByFilter = true,
        public bool $supportsDescribeIndexStats = true,
    ) {}
}
