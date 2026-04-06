<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore\Support;

/**
 * Converts a subset of Pinecone metadata filters to Qdrant filter JSON.
 *
 * Supports $and, $or, field $eq, and field $in. Logical operators combine with
 * field conditions at the same level using AND (all must hold).
 */
final class PineconeFilterForQdrant
{
    /**
     * @param  array<string, mixed>|null  $filter
     * @return array<string, mixed>|null
     */
    public static function convert(?array $filter): ?array
    {
        if ($filter === null || $filter === []) {
            return null;
        }

        return self::node($filter);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private static function node(array $filter): array
    {
        $must = [];

        if (isset($filter['$and']) && is_array($filter['$and'])) {
            foreach ($filter['$and'] as $sub) {
                if (is_array($sub)) {
                    $must[] = self::node($sub);
                }
            }
        }

        if (isset($filter['$or']) && is_array($filter['$or'])) {
            $branches = $filter['$or'];
            if ($branches === []) {
                $must[] = self::emptyOrNeverMatches();
            } else {
                $should = [];
                foreach ($branches as $sub) {
                    if (is_array($sub)) {
                        $should[] = self::node($sub);
                    }
                }
                if ($should !== []) {
                    $must[] = [
                        'should' => $should,
                        'minimum_should_match' => 1,
                    ];
                }
            }
        }

        foreach ($filter as $key => $cond) {
            if (! is_string($key) || str_starts_with($key, '$')) {
                continue;
            }
            if (! is_array($cond)) {
                continue;
            }
            if (array_key_exists('$eq', $cond)) {
                $must[] = [
                    'key' => $key,
                    'match' => ['value' => $cond['$eq']],
                ];
            }
            if (array_key_exists('$in', $cond) && is_array($cond['$in']) && $cond['$in'] !== []) {
                $shouldIn = [];
                foreach ($cond['$in'] as $val) {
                    $shouldIn[] = [
                        'key' => $key,
                        'match' => ['value' => $val],
                    ];
                }
                $must[] = [
                    'should' => $shouldIn,
                    'minimum_should_match' => 1,
                ];
            }
        }

        return ['must' => $must];
    }

    /**
     * Qdrant filter that matches no points (empty $or disjunction).
     *
     * @return array<string, mixed>
     */
    private static function emptyOrNeverMatches(): array
    {
        return [
            'must' => [
                ['key' => 'vectora__empty_or', 'match' => ['value' => 'a']],
                ['key' => 'vectora__empty_or', 'match' => ['value' => 'b']],
            ],
        ];
    }
}
