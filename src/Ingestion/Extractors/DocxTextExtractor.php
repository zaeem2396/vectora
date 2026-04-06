<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Extractors;

use Vectora\Pinecone\Contracts\TextExtractor;

/**
 * Reads plain text from OOXML (.docx) via ZipArchive — no external deps.
 */
final class DocxTextExtractor implements TextExtractor
{
    public function supports(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx';
    }

    public function extract(string $path): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ext-zip is required to read .docx files.');
        }
        if (! is_readable($path)) {
            throw new \RuntimeException(sprintf('Cannot read file [%s].', $path));
        }

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException(sprintf('Cannot open docx as zip [%s].', $path));
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new \RuntimeException('word/document.xml missing in docx.');
        }

        return $this->xmlToText($xml);
    }

    private function xmlToText(string $xml): string
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($doc === false) {
            throw new \RuntimeException('Invalid document.xml in docx.');
        }

        $namespaces = $doc->getNamespaces(true);
        $w = $namespaces['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $doc->registerXPathNamespace('w', $w);
        /** @var list<\SimpleXMLElement> $nodes */
        $nodes = $doc->xpath('//w:t') ?: [];
        $parts = [];
        foreach ($nodes as $t) {
            $parts[] = (string) $t;
        }
        $text = implode('', $parts);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
