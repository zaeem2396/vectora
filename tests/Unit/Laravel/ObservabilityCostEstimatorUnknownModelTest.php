<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;

final class ObservabilityCostEstimatorUnknownModelTest extends TestCase
{
    public function test_returns_null_when_no_rate_configured(): void
    {
        $app = new Application(dirname(__DIR__, 3));
        $app->instance('config', new Repository([
            'pinecone' => [
                'observability_v2' => [
                    'costs' => [
                        'embedding_usd_per_1m_tokens' => [],
                    ],
                ],
            ],
        ]));

        $est = new ObservabilityCostEstimator($app);

        $this->assertNull($est->estimateEmbeddingUsd('unknown-model', 100));
    }
}
