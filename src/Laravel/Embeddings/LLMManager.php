<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Embeddings;

use Vectora\Pinecone\Contracts\LLMDriver;

/** Resolves configured {@see LLMDriver} implementations. */
final class LLMManager
{
    public function __construct(
        private readonly LLMDriverFactory $factory,
    ) {}

    public function driver(?string $name = null): LLMDriver
    {
        return $this->factory->make($name);
    }
}
