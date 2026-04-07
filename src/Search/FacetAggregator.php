<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Search;

use Vectora\Pinecone\DTO\QueryVectorMatch;

/**
 * Client-side facet counts from query hits (Phase 10). Not a full index facet engine.
 */
final class FacetAggregator
{
    /**
     * @param  list<QueryVectorMatch>  $matches
     * @param  list<string>  $metadataKeys
     * @return array<string, array<string, int>> Key = metadata field, value = map value string => count
     */
    public static function aggregate(array $matches, array $metadataKeys): array
    {
        $out = [];
        foreach ($metadataKeys as $key) {
            $out[$key] = [];
        }
        foreach ($matches as $m) {
            $meta = $m->metadata ?? [];
            foreach ($metadataKeys as $key) {
                if (! array_key_exists($key, $meta)) {
                    continue;
                }
                $v = $meta[$key];
                $label = is_scalar($v) ? (string) $v : json_encode($v);
                $out[$key][$label] = ($out[$key][$label] ?? 0) + 1;
            }
        }

        return $out;
    }
}
