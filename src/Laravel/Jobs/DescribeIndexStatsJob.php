<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Throwable;
use Vectora\Pinecone\Laravel\Events\VectorFailed;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

/** Async describe_index_stats (metrics / health checks). */
final class DescribeIndexStatsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ?string $index = null,
    ) {
        $q = config('pinecone.queue', []);
        if (isset($q['connection']) && is_string($q['connection']) && $q['connection'] !== '') {
            $this->onConnection($q['connection']);
        }
        if (isset($q['queue']) && is_string($q['queue']) && $q['queue'] !== '') {
            $this->onQueue($q['queue']);
        }
    }

    public function handle(PineconeClientFactory $factory): void
    {
        try {
            $stats = $factory->vectorStore($this->index)->describeIndexStats();

            Event::dispatch(new VectorSynced('describe_index_stats', [
                'index' => $this->index,
                'totalVectorCount' => $stats->totalVectorCount,
                'dimension' => $stats->dimension,
            ]));
        } catch (Throwable $e) {
            Event::dispatch(new VectorFailed('describe_index_stats', $e->getMessage(), [
                'index' => $this->index,
            ]));

            throw $e;
        }
    }
}
