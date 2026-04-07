<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Fixtures;

use Illuminate\Database\Eloquent\SoftDeletes;
use Vectora\Pinecone\Eloquent\AbstractEmbeddableModel;
use Vectora\Pinecone\Eloquent\Attributes\EmbeddingColumns;

#[EmbeddingColumns(columns: ['title', 'body'])]
class EmbeddableArticleAttributed extends AbstractEmbeddableModel
{
    use SoftDeletes;

    protected $table = 'embeddable_articles';

    /** @var list<string> */
    protected $fillable = ['title', 'body'];
}
