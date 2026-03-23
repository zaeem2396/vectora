<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Commands;

use Illuminate\Console\Command;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class PineconeSyncCommand extends Command
{
    protected $signature = 'pinecone:sync
                            {--index= : Logical index name}
                            {--namespace= : Optional namespace filter for stats}';

    protected $description = 'Describe index stats (connectivity / counts) for the configured index';

    public function handle(PineconeClientFactory $factory): int
    {
        $index = $this->option('index') ? (string) $this->option('index') : null;
        $ns = $this->option('namespace');
        $namespace = $ns !== null && $ns !== false ? (string) $ns : null;
        if ($namespace === '') {
            $namespace = null;
        }

        $stats = $factory->vectorStore($index)->describeIndexStats($namespace);

        $this->table(
            ['Metric', 'Value'],
            [
                ['dimension', (string) $stats->dimension],
                ['totalVectorCount', (string) $stats->totalVectorCount],
                ['metric', $stats->metric ?? '—'],
            ]
        );

        if ($stats->namespaces !== []) {
            $rows = [];
            foreach ($stats->namespaces as $summary) {
                $rows[] = [$summary->name !== '' ? $summary->name : '(default)', (string) $summary->vectorCount];
            }
            $this->newLine();
            $this->info('Namespaces');
            $this->table(['Namespace', 'vectorCount'], $rows);
        }

        return self::SUCCESS;
    }
}
