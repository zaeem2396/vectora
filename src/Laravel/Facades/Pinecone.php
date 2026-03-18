<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vectora\Pinecone\Laravel\PineconeManager
 */
class Pinecone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'vectora.pinecone';
    }
}
