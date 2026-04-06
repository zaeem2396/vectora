<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion\Extractors;

use Vectora\Pinecone\Contracts\TextExtractor;

/** Strips tags and collapses whitespace for HTML / htm files. */
final class HtmlTextExtractor implements TextExtractor
{
    public function supports(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['html', 'htm', 'xhtml'], true);
    }

    public function extract(string $path): string
    {
        if (! is_readable($path)) {
            throw new \RuntimeException(sprintf('Cannot read file [%s].', $path));
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException(sprintf('Failed to read file [%s].', $path));
        }

        return $this->htmlToText($raw);
    }

    public function htmlToText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
