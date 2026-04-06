<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore\Support;

/**
 * Deterministic UUIDv5 from namespace + logical id.
 * Used for Weaviate object ids and Qdrant point ids (Qdrant requires UUID or integer, not long hex strings).
 */
final class WeaviateUuid
{
    /** DNS namespace UUID per RFC 4122. */
    private const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    public static function fromNamespaceAndId(string $namespace, string $id): string
    {
        $nsHex = str_replace('-', '', self::NAMESPACE_DNS);
        $n = hex2bin($nsHex);
        if ($n === false || strlen($n) !== 16) {
            throw new \RuntimeException('Invalid namespace UUID.');
        }
        $hash = sha1($n.$namespace."\0".$id, true);
        if (strlen($hash) < 16) {
            throw new \RuntimeException('Unexpected SHA1 length.');
        }
        $hash = substr($hash, 0, 16);
        $hash[6] = chr((ord($hash[6]) & 0x0F) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3F) | 0x80);

        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($hash, 0, 4)),
            bin2hex(substr($hash, 4, 2)),
            bin2hex(substr($hash, 6, 2)),
            bin2hex(substr($hash, 8, 2)),
            bin2hex(substr($hash, 10, 6))
        );
    }
}
