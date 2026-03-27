<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Observability\ObservabilityHooks;

final class ObservabilityHooksStackTest extends TestCase
{
    public function test_stack_with_no_arguments_returns_null(): void
    {
        $this->assertNull(ObservabilityHooks::stack());
    }

    public function test_stack_single_returns_equivalent_behavior(): void
    {
        $seen = false;
        $one = new ObservabilityHooks(
            beforeRequest: static function () use (&$seen): void {
                $seen = true;
            },
        );
        $stacked = ObservabilityHooks::stack($one);
        $this->assertNotNull($stacked);
        $stacked->beforeRequest(new Request('GET', 'http://example.test'));
        $this->assertTrue($seen);
    }

    public function test_stack_invokes_hooks_in_order(): void
    {
        $order = [];
        $a = new ObservabilityHooks(
            beforeRequest: static function () use (&$order): void {
                $order[] = 'a';
            },
        );
        $b = new ObservabilityHooks(
            beforeRequest: static function () use (&$order): void {
                $order[] = 'b';
            },
        );
        $s = ObservabilityHooks::stack($a, $b);
        $this->assertNotNull($s);
        $s->beforeRequest(new Request('GET', 'http://example.test'));
        $this->assertSame(['a', 'b'], $order);
    }

    public function test_stack_after_response_invokes_in_order(): void
    {
        $order = [];
        $req = new Request('GET', 'http://example.test');
        $res = new Response(200);
        $a = new ObservabilityHooks(
            afterResponse: static function () use (&$order): void {
                $order[] = 'a';
            },
        );
        $b = new ObservabilityHooks(
            afterResponse: static function () use (&$order): void {
                $order[] = 'b';
            },
        );
        $s = ObservabilityHooks::stack($a, $b);
        $this->assertNotNull($s);
        $s->afterResponse($req, $res);
        $this->assertSame(['a', 'b'], $order);
    }
}
