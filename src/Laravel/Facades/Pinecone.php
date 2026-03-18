<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Vectora\Pinecone\Laravel\PineconeManager;

/**
 * @see PineconeManager
 */
class Pinecone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'vectora.pinecone';
    }
}
