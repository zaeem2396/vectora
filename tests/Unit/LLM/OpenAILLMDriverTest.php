<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\LLM;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\LLM\OpenAILLMDriver;

final class OpenAILLMDriverTest extends TestCase
{
    public function test_chat_parses_message_content(): void
    {
        $mock = new MockHandler([
            new Response(200, [], Json::encode([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Hello world']],
                ],
            ])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $f = new HttpFactory;
        $d = new OpenAILLMDriver($client, $f, $f, 'k', 'gpt-test');
        $text = $d->chat([['role' => 'user', 'content' => 'hi']]);
        $this->assertSame('Hello world', $text);
    }

    public function test_stream_parses_sse_deltas(): void
    {
        $sse = 'data: '.Json::encode(['choices' => [['delta' => ['content' => 'Hel']]]])."\n\n"
            .'data: '.Json::encode(['choices' => [['delta' => ['content' => 'lo']]]])."\n\n"
            ."data: [DONE]\n\n";
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/event-stream'], $sse),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $f = new HttpFactory;
        $d = new OpenAILLMDriver($client, $f, $f, 'k', 'gpt-test');
        $parts = iterator_to_array($d->streamChat([['role' => 'user', 'content' => 'x']]));
        $this->assertSame(['Hel', 'lo'], $parts);
    }
}
