<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Rag;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Rag\InMemoryConversationMemory;

final class InMemoryConversationMemoryTest extends TestCase
{
    public function test_roundtrip_turns(): void
    {
        $m = new InMemoryConversationMemory;
        $m->addUser('hi');
        $m->addAssistant('hello');
        $this->assertCount(2, $m->messages());
        $m->clear();
        $this->assertSame([], $m->messages());
    }
}
