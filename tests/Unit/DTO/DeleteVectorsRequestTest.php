<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;

final class DeleteVectorsRequestTest extends TestCase
{
    public function test_rejects_ambiguous_modes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DeleteVectorsRequest(ids: ['a'], filter: ['x' => 1]);
    }

    public function test_ids_payload(): void
    {
        $r = new DeleteVectorsRequest(namespace: 'ns', ids: ['x', 'y']);
        $this->assertSame([
            'namespace' => 'ns',
            'ids' => ['x', 'y'],
        ], $r->toApiBody());
    }

    public function test_delete_all_payload(): void
    {
        $r = new DeleteVectorsRequest(deleteAll: true);
        $this->assertTrue($r->toApiBody()['deleteAll']);
    }
}
