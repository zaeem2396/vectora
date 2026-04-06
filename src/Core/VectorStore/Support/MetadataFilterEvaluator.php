<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore\Support;

/** Subset of Pinecone metadata filters for local / SQLite backends ($eq, $and, $in). */
final class MetadataFilterEvaluator
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $filter
     */
    public static function matches(?array $metadata, ?array $filter): bool
    {
        if ($filter === null || $filter === []) {
            return true;
        }
        $meta = $metadata ?? [];

        return self::evaluate($meta, $filter);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $filter
     */
    private static function evaluate(array $meta, array $filter): bool
    {
        if (isset($filter['$and']) && is_array($filter['$and'])) {
            foreach ($filter['$and'] as $sub) {
                if (! is_array($sub) || ! self::evaluate($meta, $sub)) {
                    return false;
                }
            }
        }

        if (isset($filter['$or']) && is_array($filter['$or'])) {
            $branches = $filter['$or'];
            if ($branches === []) {
                return false;
            }
            $any = false;
            foreach ($branches as $sub) {
                if (is_array($sub) && self::evaluate($meta, $sub)) {
                    $any = true;
                    break;
                }
            }
            if (! $any) {
                return false;
            }
        }

        foreach ($filter as $key => $cond) {
            if (! is_string($key) || str_starts_with($key, '$')) {
                continue;
            }
            if (! is_array($cond)) {
                continue;
            }
            $val = $meta[$key] ?? null;
            if (array_key_exists('$eq', $cond)) {
                $expected = $cond['$eq'];
                if ($expected === null) {
                    if ($val !== null) {
                        return false;
                    }
                } elseif ($val !== $expected && (string) $val !== (string) $expected) {
                    return false;
                }
            }
            if (array_key_exists('$in', $cond) && is_array($cond['$in'])) {
                if (! in_array($val, $cond['$in'], true) && ! in_array((string) $val, array_map('strval', $cond['$in']), true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
