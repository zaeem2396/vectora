<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Exceptions;

use InvalidArgumentException;
use Vectora\Pinecone\Contracts\Embeddable;

/**
 * Thrown when semantic search or semantic query builder APIs receive invalid arguments.
 */
final class SemanticSearchInvalidArgumentException extends InvalidArgumentException
{
    public static function topKTooLow(): self
    {
        return new self(
            'Semantic search topK must be at least 1. Pass a positive integer for how many vector matches to retrieve from the index.'
        );
    }

    /**
     * @param  class-string  $class
     */
    public static function modelMustImplementEmbeddable(string $class): self
    {
        return new self(
            'Semantic query builder methods require an '.Embeddable::class.' model; '.$class.' does not implement it.'
        );
    }
}
