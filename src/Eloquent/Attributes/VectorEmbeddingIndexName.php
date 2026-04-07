<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent\Attributes;

use Attribute;
use ReflectionClass;
use Vectora\Pinecone\Contracts\Embeddable;

/**
 * Optional logical Pinecone index name for {@see Embeddable::vectorEmbeddingIndex()}.
 *
 * @example #[VectorEmbeddingIndexName('posts')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class VectorEmbeddingIndexName
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}

    public static function read(string $class): ?string
    {
        $ref = new ReflectionClass($class);
        foreach ($ref->getAttributes(self::class) as $attr) {
            /** @var self $instance */
            $instance = $attr->newInstance();

            return $instance->name;
        }

        return null;
    }
}
