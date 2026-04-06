<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Vectora\Pinecone\Laravel\Rag\RagQueryBuilder;
use Vectora\Pinecone\Laravel\Rag\RagQueryFactory;

/**
 * @method static RagQueryBuilder using(string $modelClass)
 * @method static \Vectora\Pinecone\Laravel\Ingestion\IngestionBuilder ingest()
 *
 * @see RagQueryFactory
 */
final class Vector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RagQueryFactory::class;
    }
}
