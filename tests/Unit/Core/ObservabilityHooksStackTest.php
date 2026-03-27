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
