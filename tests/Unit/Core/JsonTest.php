<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\Json;

final class JsonTest extends TestCase
{
    public function test_encode_decode_roundtrip(): void
    {
        $data = ['a' => 1, 'b' => 'x'];
        $this->assertSame($data, Json::decodeObject(Json::encode($data)));
    }
}
