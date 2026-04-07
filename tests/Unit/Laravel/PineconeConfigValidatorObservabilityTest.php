<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Support\PineconeConfigValidator;

final class PineconeConfigValidatorObservabilityTest extends TestCase
{
    public function test_rejects_non_array_observability_v2_section(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.observability_v2 must be an array');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'observability_v2' => 'bad',
        ]);
    }

    public function test_rejects_non_boolean_observability_v2_flag_when_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.observability_v2.enabled');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'observability_v2' => ['enabled' => 'yes'],
        ]);
    }

    public function test_rejects_non_numeric_cost_table_entry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('embedding_usd_per_1m_tokens');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'observability_v2' => [
                'enabled' => true,
                'costs' => [
                    'embedding_usd_per_1m_tokens' => [
                        'text-embedding-3-small' => 'free',
                    ],
                ],
            ],
        ]);
    }
}
