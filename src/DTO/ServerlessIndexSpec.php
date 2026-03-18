<?php

declare(strict_types=1);

namespace Vectora\Pinecone\DTO;

/** Pinecone serverless index spec (control plane). */
final readonly class ServerlessIndexSpec
{
    public function __construct(
        public string $cloud,
        public string $region,
    ) {}

    /**
     * @return array{serverless: array{cloud: string, region: string}}
     */
    public function toSpecArray(): array
    {
        return [
            'serverless' => [
                'cloud' => $this->cloud,
                'region' => $this->region,
            ],
        ];
    }
}
