<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VectorFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $operation,
        public string $message,
        public array $context = [],
    ) {}
}
