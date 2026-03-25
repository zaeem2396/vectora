<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class ModelUpdateSkipsWhenIrrelevantFieldChangesTest extends EmbeddingsFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('embeddable_articles', function (Blueprint $table): void {
            $table->string('slug')->default('');
        });
    }

    public function test_skips_upsert_when_only_non_embedding_columns_change(): void
    {
        $article = EmbeddableArticleWithSlug::create(['title' => 'T', 'body' => 'B', 'slug' => 'a']);
        $this->recordingStore->upsertRequests = [];

        $article->update(['slug' => 'b']);

        $this->assertCount(0, $this->recordingStore->upsertRequests);
    }
}

/**
 * @property string $slug
 */
final class EmbeddableArticleWithSlug extends EmbeddableArticle
{
    /** @var list<string> */
    protected $fillable = ['title', 'body', 'slug'];
}
