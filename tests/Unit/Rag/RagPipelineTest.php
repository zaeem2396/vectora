<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Rag;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Contracts\RagRetrieverContract;
use Vectora\Pinecone\DTO\RagSourceChunk;
use Vectora\Pinecone\LLM\StubLLMDriver;
use Vectora\Pinecone\Rag\InMemoryConversationMemory;
use Vectora\Pinecone\Rag\RagPipeline;
use Vectora\Pinecone\Rag\RagPromptBuilder;

final class RagPipelineTest extends TestCase
{
    public function test_ask_returns_answer_and_sources(): void
    {
        $retriever = new class implements RagRetrieverContract
        {
            public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array
            {
                return [new RagSourceChunk('x', 'context body', 0.99, [])];
            }
        };
        $pipeline = new RagPipeline($retriever, new StubLLMDriver('OUT: '), new RagPromptBuilder);
        $ans = $pipeline->ask('Q?');
        $this->assertStringContainsString('Q?', $ans->text);
        $this->assertCount(1, $ans->sources);
        $this->assertSame('x', $ans->sources[0]->id);
    }

    public function test_stream_yields_chunks(): void
    {
        $retriever = new class implements RagRetrieverContract
        {
            public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array
            {
                return [];
            }
        };
        $pipeline = new RagPipeline($retriever, new StubLLMDriver);
        $parts = iterator_to_array($pipeline->streamAsk('Hi'));
        $this->assertNotSame([], $parts);
    }

    public function test_memory_records_turns(): void
    {
        $retriever = new class implements RagRetrieverContract
        {
            public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array
            {
                return [];
            }
        };
        $memory = new InMemoryConversationMemory;
        $pipeline = new RagPipeline($retriever, new StubLLMDriver, new RagPromptBuilder, $memory);
        $pipeline->ask('one');
        $pipeline->ask('two');
        $this->assertCount(4, $memory->messages());
    }
}
