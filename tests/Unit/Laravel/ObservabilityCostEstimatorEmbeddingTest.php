<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;

final class ObservabilityCostEstimatorEmbeddingTest extends TestCase
{
    public function test_embedding_estimate_uses_per_million_rate(): void
    {
        $app = new Application(dirname(__DIR__, 3));
        $app->instance('config', new Repository([
            'pinecone' => [
                'observability_v2' => [
                    'costs' => [
                        'embedding_usd_per_1m_tokens' => [
                            'text-embedding-3-small' => 0.02,
                        ],
                    ],
                ],
            ],
        ]));

        $est = new ObservabilityCostEstimator($app);
        $usd = $est->estimateEmbeddingUsd('text-embedding-3-small', 1_000_000);

        $this->assertNotNull($usd);
        $this->assertEqualsWithDelta(0.02, $usd, 0.00000001);
    }
}
