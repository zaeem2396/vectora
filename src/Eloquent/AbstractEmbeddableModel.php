<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Eloquent\Concerns\HasEmbeddings;

/**
 * Optional base class for Eloquent models that sync vectors. You may instead
 * implement {@see Embeddable} and use {@see HasEmbeddings} on any {@see Model}.
 */
abstract class AbstractEmbeddableModel extends Model implements Embeddable
{
    use HasEmbeddings;
}
