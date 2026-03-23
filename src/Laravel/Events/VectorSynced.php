<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VectorSynced
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $operation,
        public array $context = [],
    ) {}
}
