<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\LLM;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\LLM\StubLLMDriver;

final class StubLLMDriverTest extends TestCase
{
    public function test_chat_uses_last_user_message(): void
    {
        $d = new StubLLMDriver('X: ');
        $text = $d->chat([
            ['role' => 'system', 'content' => 'sys'],
            ['role' => 'user', 'content' => 'hello'],
        ]);
        $this->assertSame('X: hello', $text);
    }

    public function test_stream_yields_single_chunk(): void
    {
        $d = new StubLLMDriver;
        $out = iterator_to_array($d->streamChat([['role' => 'user', 'content' => 'z']]));
        $this->assertSame(['STUB: z'], $out);
    }
}
