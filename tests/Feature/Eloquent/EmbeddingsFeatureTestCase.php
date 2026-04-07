<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Feature\Laravel\PineconeFeatureTestCase;
use Vectora\Pinecone\Tests\Support\FixedVectorStorePineconeClientFactory;
use Vectora\Pinecone\Tests\Support\RecordingVectorStore;

abstract class EmbeddingsFeatureTestCase extends PineconeFeatureTestCase
{
    protected RecordingVectorStore $recordingStore;

    private bool $embeddingsSchemaCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('SQLite PDO driver required for Eloquent embedding tests (e.g. php-sqlite3).');
        }

        $this->mergePineconeConfig([
            'eloquent' => ['default_sync' => 'sync'],
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => ['enabled' => false],
            ],
        ]);

        Schema::create('embeddable_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->default('');
            $table->text('body')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->embeddingsSchemaCreated = true;

        $this->recordingStore = new RecordingVectorStore;
        $this->recordingStore->queryCallCount = 0;
        $this->instance(PineconeClientFactory::class, new FixedVectorStorePineconeClientFactory(
            $this->app,
            $this->recordingStore,
            Mockery::mock(IndexAdminContract::class),
        ));
    }

    protected function tearDown(): void
    {
        if ($this->embeddingsSchemaCreated) {
            Schema::dropIfExists('embeddable_articles');
            $this->embeddingsSchemaCreated = false;
        }
        parent::tearDown();
    }
}
