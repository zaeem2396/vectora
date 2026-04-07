<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Laravel\PineconeManager;

/**
 * @method static VectorStoreContract connection(?string $name = null)
 * @method static IndexAdminContract admin()
 * @method static EmbeddingDriver embeddings(?string $driver = null)
 * @method static list<float> embed(string $text)
 * @method static list<list<float>> embedMany(list<string> $texts)
 * @method static \Vectora\Pinecone\Laravel\Search\AdvancedSearchBuilder advancedSearch(?string $index = null, ?string $embeddingDriver = null)
 * @method static mixed config(?string $key = null, mixed $default = null)
 *
 * @see PineconeManager
 */
class Pinecone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'vectora.pinecone';
    }
}
