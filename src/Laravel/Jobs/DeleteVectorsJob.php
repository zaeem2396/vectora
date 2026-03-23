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
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\Laravel\Events\VectorFailed;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class DeleteVectorsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>|null  $ids
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        public ?string $namespace = null,
        public ?array $ids = null,
        public ?array $filter = null,
        public bool $deleteAll = false,
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
            $request = new DeleteVectorsRequest(
                namespace: $this->namespace,
                ids: $this->ids,
                filter: $this->filter,
                deleteAll: $this->deleteAll,
            );
            $factory->vectorStore($this->index)->delete($request);

            Event::dispatch(new VectorSynced('delete', [
                'index' => $this->index,
                'namespace' => $this->namespace,
                'delete_all' => $this->deleteAll,
            ]));
        } catch (Throwable $e) {
            Event::dispatch(new VectorFailed('delete', $e->getMessage(), [
                'index' => $this->index,
                'namespace' => $this->namespace,
            ]));

            throw $e;
        }
    }
}
