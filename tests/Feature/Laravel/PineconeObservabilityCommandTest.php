<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

final class PineconeObservabilityCommandTest extends PineconeFeatureTestCase
{
    public function test_pinecone_observability_command_succeeds(): void
    {
        $this->artisan('pinecone:observability')->assertSuccessful();
    }
}
