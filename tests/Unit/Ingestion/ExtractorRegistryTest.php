<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Ingestion;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Ingestion\ExtractorRegistry;

final class ExtractorRegistryTest extends TestCase
{
    public function test_plain_text_roundtrip(): void
    {
        $path = sys_get_temp_dir().'/vectora-ingest-'.uniqid('', true).'.txt';
        file_put_contents($path, "line one\nline two");
        try {
            $r = new ExtractorRegistry;
            $this->assertSame("line one\nline two", $r->extractFromPath($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_unknown_extension_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ExtractorRegistry)->extractFromPath('/no/match.xyzunknown');
    }
}
