<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Vectora\Pinecone\Contracts\Embeddable;

/**
 * Virtual column: concatenates multiple DB columns with newlines for embedding text (see Phase 11 DX).
 *
 * Use in {@see Model::$casts} as:
 * `'embedding_text' => ConcatEmbeddingTextCast::class.':title,body'`
 *
 * Then point {@see Embeddable::vectorEmbeddingFields()} at `embedding_text` alone.
 *
 * @implements CastsAttributes<string, null>
 */
final class ConcatEmbeddingTextCast implements Castable, CastsAttributes
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(
        private readonly array $columns,
    ) {}

    /**
     * @param  array<int, string>  $arguments
     */
    public static function castUsing(array $arguments): static
    {
        return new self(array_values($arguments));
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        $parts = [];
        foreach ($this->columns as $col) {
            $parts[] = (string) ($attributes[$col] ?? '');
        }

        return trim(implode("\n", $parts));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return null;
    }
}
