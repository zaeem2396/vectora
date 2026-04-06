<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Vectora\Pinecone\DTO\VectorStoreCapabilities;

/** Optional contract for backends that advertise supported operations. */
interface ProvidesVectorStoreCapabilities
{
    public function vectorStoreCapabilities(): VectorStoreCapabilities;
}
