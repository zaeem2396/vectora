<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Eloquent\Casts\ConcatEmbeddingTextCast;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class ConcatEmbeddingTextCastTest extends TestCase
{
    public function test_concatenates_configured_columns(): void
    {
        $cast = new ConcatEmbeddingTextCast(['title', 'body']);
        $model = new EmbeddableArticle;
        $model->forceFill(['title' => 'Hello', 'body' => 'World']);

        $this->assertSame("Hello\nWorld", $cast->get($model, 'embedding', null, $model->getAttributes()));
    }
}
