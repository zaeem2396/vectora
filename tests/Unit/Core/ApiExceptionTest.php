<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Exception\ApiErrorCategory;
use Vectora\Pinecone\Core\Exception\ApiException;

final class ApiExceptionTest extends TestCase
{
    public function test_is_rate_limited(): void
    {
        $e = new ApiException('x', 429);
        $this->assertTrue($e->isRateLimited());
        $this->assertFalse((new ApiException('y', 400))->isRateLimited());
    }
