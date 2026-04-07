<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Artisan;
use Vectora\Pinecone\Tests\Feature\Eloquent\EmbeddingsFeatureTestCase;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SemanticDebugCommandTest extends EmbeddingsFeatureTestCase
{
    public function test_outputs_json_when_semantic_debug_enabled(): void
    {
        $this->mergePineconeConfig(['dx' => ['semantic_debug' => true]]);

        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'T', 'body' => '']);
        });

        $this->recordingStore->queryMatches = [];

        $exit = Artisan::call('pinecone:semantic-debug', [
            'model' => EmbeddableArticle::class,
            'query' => 'hello world',
            '--top' => 3,
        ]);

        $this->assertSame(0, $exit);
        $out = Artisan::output();
        $this->assertStringContainsString('"query": "hello world"', $out);
        $decoded = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(EmbeddableArticle::class, $decoded['model']);
        $this->assertSame('hello world', $decoded['query']);
        $this->assertSame(3, $decoded['topK']);
    }

    public function test_fails_when_semantic_debug_disabled(): void
    {
        $this->mergePineconeConfig(['dx' => ['semantic_debug' => false]]);

        $exit = Artisan::call('pinecone:semantic-debug', [
            'model' => EmbeddableArticle::class,
            'query' => 'x',
        ]);

        $this->assertSame(1, $exit);
    }
}
