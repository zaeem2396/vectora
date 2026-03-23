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
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\Events\VectorFailed;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class UpsertVectorsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<array{id: string, values: list<float>, metadata?: array<string, mixed>}>  $vectors
     */
    public function __construct(
        public array $vectors,
        public ?string $namespace = null,
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
            $records = [];
            foreach ($this->vectors as $row) {
                $records[] = new VectorRecord(
                    (string) $row['id'],
                    array_map('floatval', $row['values']),
                    isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : null,
                );
            }
            $request = new UpsertVectorsRequest($records, $this->namespace);
            $factory->vectorStore($this->index)->upsert($request);

            Event::dispatch(new VectorSynced('upsert', [
                'count' => count($this->vectors),
                'index' => $this->index,
                'namespace' => $this->namespace,
            ]));
        } catch (Throwable $e) {
            Event::dispatch(new VectorFailed('upsert', $e->getMessage(), [
                'index' => $this->index,
                'namespace' => $this->namespace,
            ]));

            throw $e;
        }
    }
}
