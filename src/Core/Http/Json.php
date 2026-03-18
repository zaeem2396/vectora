<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Http;

use JsonException;

final class Json
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \InvalidArgumentException('JSON encode failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeObject(string $json): array
    {
        try {
            $v = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \InvalidArgumentException('JSON decode failed: '.$e->getMessage(), 0, $e);
        }
        if (! is_array($v)) {
            throw new \InvalidArgumentException('Expected JSON object.');
        }

        return $v;
    }
}
