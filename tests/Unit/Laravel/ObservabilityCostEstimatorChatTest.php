<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Observability\ObservabilityCostEstimator;

final class ObservabilityCostEstimatorChatTest extends TestCase
{
    public function test_chat_estimate_sums_prompt_and_completion_tokens(): void
    {
        $app = new Application(dirname(__DIR__, 3));
        $app->instance('config', new Repository([
            'pinecone' => [
                'observability_v2' => [
                    'costs' => [
                        'chat_usd_per_1m_tokens' => [
                            'gpt-4o-mini' => 0.15,
                        ],
                    ],
                ],
            ],
        ]));

        $est = new ObservabilityCostEstimator($app);
        $usd = $est->estimateChatUsd('gpt-4o-mini', 500_000, 500_000);

        $this->assertNotNull($usd);
        $this->assertEqualsWithDelta(0.15, $usd, 0.00000001);
    }
}
