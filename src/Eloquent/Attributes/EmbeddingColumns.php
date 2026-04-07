<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent\Attributes;

use Attribute;
use ReflectionClass;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Eloquent\Concerns\UsesEmbeddingColumnsAttribute;

/**
 * Declares which Eloquent attributes feed {@see Embeddable::vectorEmbeddingText()}
 * when used with {@see UsesEmbeddingColumnsAttribute}.
 *
 * @example #[EmbeddingColumns(columns: ['title', 'body'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EmbeddingColumns
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(
        public array $columns,
    ) {}

    /**
     * @return list<string>
     */
    public static function read(string $class): array
    {
        $ref = new ReflectionClass($class);
        foreach ($ref->getAttributes(self::class) as $attr) {
            /** @var self $instance */
            $instance = $attr->newInstance();

            return $instance->columns;
        }

        return [];
    }
}
