<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Ingestion;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Ingestion\Extractors\HtmlTextExtractor;

final class HtmlTextExtractorTest extends TestCase
{
    public function test_html_to_text_strips_tags(): void
    {
        $h = new HtmlTextExtractor;
        $t = $h->htmlToText('<p>Hello <b>world</b></p>');
        $this->assertStringContainsString('Hello', $t);
        $this->assertStringContainsString('world', $t);
        $this->assertStringNotContainsString('<', $t);
    }
}
