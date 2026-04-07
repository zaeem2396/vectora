<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticleAttributed;

final class EmbeddingColumnsAttributeTest extends TestCase
{
    public function test_resolves_vector_embedding_fields_from_attribute(): void
    {
        $this->assertSame(['title', 'body'], EmbeddableArticleAttributed::vectorEmbeddingFields());
    }
}
