<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Extractors;

use Smalot\PdfParser\Parser;
use Vectora\Pinecone\Contracts\TextExtractor;

/** Uses smalot/pdf-parser (composer) for text extraction. */
final class PdfTextExtractor implements TextExtractor
{
    public function supports(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function extract(string $path): string
    {
        if (! is_readable($path)) {
            throw new \RuntimeException(sprintf('Cannot read file [%s].', $path));
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText();
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
