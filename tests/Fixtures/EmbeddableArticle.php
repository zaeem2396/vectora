<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Fixtures;

use Illuminate\Database\Eloquent\SoftDeletes;
use Vectora\Pinecone\Eloquent\AbstractEmbeddableModel;

class EmbeddableArticle extends AbstractEmbeddableModel
{
    use SoftDeletes;

    protected $table = 'embeddable_articles';

    /** @var list<string> */
    protected $fillable = ['title', 'body'];

    /**
     * @return list<string>
     */
    public static function vectorEmbeddingFields(): array
    {
        return ['title', 'body'];
    }
}
