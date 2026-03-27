<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Exception\ApiErrorCategory;

final class ApiErrorCategoryTest extends TestCase
{
    /**
     * @return iterable<string, array{int, ApiErrorCategory}>
     */
    public static function statusCodeProvider(): iterable
    {
        yield '429' => [429, ApiErrorCategory::RateLimited];
        yield '401' => [401, ApiErrorCategory::Authentication];
        yield '403' => [403, ApiErrorCategory::Authentication];
        yield '404' => [404, ApiErrorCategory::NotFound];
        yield '400' => [400, ApiErrorCategory::BadRequest];
        yield '422' => [422, ApiErrorCategory::BadRequest];
        yield '418' => [418, ApiErrorCategory::Client];
        yield '500' => [500, ApiErrorCategory::Server];
        yield '599' => [599, ApiErrorCategory::Server];
    }

    #[DataProvider('statusCodeProvider')]
    public function test_from_status_code_maps_expected_category(int $code, ApiErrorCategory $expected): void
    {
        $this->assertSame($expected, ApiErrorCategory::fromStatusCode($code));
    }

    public function test_unknown_codes_grouped_as_unknown(): void
    {
        $this->assertSame(ApiErrorCategory::Unknown, ApiErrorCategory::fromStatusCode(0));
        $this->assertSame(ApiErrorCategory::Unknown, ApiErrorCategory::fromStatusCode(200));
        $this->assertSame(ApiErrorCategory::Unknown, ApiErrorCategory::fromStatusCode(600));
    }
}
