<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class VectorUpsertUsesCustomNamespaceTest extends EmbeddingsFeatureTestCase
{
    public function test_static_namespace_is_passed_to_upsert(): void
    {
        NamespacedEmbeddableArticle::create(['title' => 'A', 'body' => 'B']);

        $ns = $this->recordingStore->upsertRequests[0]->namespace;
        $this->assertSame('my-namespace', $ns);
    }
}

final class NamespacedEmbeddableArticle extends EmbeddableArticle
{
    public static function vectorEmbeddingNamespace(): ?string
    {
        return 'my-namespace';
    }
}
