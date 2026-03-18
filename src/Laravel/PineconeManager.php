<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel;

/**
 * Laravel-facing entry point for vector operations. Core client wiring added in Phase 2.
 */
class PineconeManager
{
    public function __construct(
        protected array $config = [],
    ) {
    }
}
