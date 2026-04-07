<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Search;

use Vectora\Pinecone\DTO\QueryVectorMatch;

/**
 * Normalizes vector similarity scores to comparable ranges (Phase 10).
 */
final class ScoreNormalizer
{
    /**
     * @param  list<QueryVectorMatch>  $matches
     * @return list<QueryVectorMatch> New matches with scores scaled to [0, 1] when range &gt; 0
     */
    public static function minMax(array $matches): array
    {
        if ($matches === []) {
            return [];
        }
        $scores = array_map(static fn (QueryVectorMatch $m): float => $m->score, $matches);
        $min = min($scores);
        $max = max($scores);
        $range = $max - $min;
        if ($range <= 0.0) {
            return array_map(
                static fn (QueryVectorMatch $m): QueryVectorMatch => new QueryVectorMatch(
                    $m->id,
                    1.0,
                    $m->values,
                    $m->metadata,
                ),
                $matches
            );
        }
        $out = [];
        foreach ($matches as $m) {
            $norm = ($m->score - $min) / $range;
            $out[] = new QueryVectorMatch($m->id, $norm, $m->values, $m->metadata);
        }

        return $out;
    }

    /**
     * Softmax over scores (stable).
     *
     * @param  list<QueryVectorMatch>  $matches
     * @return list<QueryVectorMatch>
     */
    public static function softmax(array $matches, float $temperature = 1.0): array
    {
        if ($matches === []) {
            return [];
        }
        $temperature = max(1e-9, $temperature);
        $scores = array_map(static fn (QueryVectorMatch $m): float => $m->score, $matches);
        $max = max($scores);
        $expSum = 0.0;
        $exps = [];
        foreach ($scores as $i => $s) {
            $exps[$i] = exp(($s - $max) / $temperature);
            $expSum += $exps[$i];
        }
        if ($expSum <= 0.0) {
            return self::minMax($matches);
        }
        $out = [];
        foreach ($matches as $i => $m) {
            $out[] = new QueryVectorMatch($m->id, $exps[$i] / $expSum, $m->values, $m->metadata);
        }

        return $out;
    }
}
