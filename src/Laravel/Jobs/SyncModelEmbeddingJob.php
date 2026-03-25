<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Throwable;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Laravel\Events\VectorFailed;
use Vectora\Pinecone\Laravel\Events\VectorSynced;

final class SyncModelEmbeddingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Model $model,
    ) {
        $q = config('pinecone.queue', []);
        if (isset($q['connection']) && is_string($q['connection']) && $q['connection'] !== '') {
            $this->onConnection($q['connection']);
        }
        if (isset($q['queue']) && is_string($q['queue']) && $q['queue'] !== '') {
            $this->onQueue($q['queue']);
        }
    }

    public function handle(): void
    {
        $model = $this->model;
        if (! $model instanceof Embeddable) {
            return;
        }

        try {
            if (! $model->shouldSyncVectorEmbedding()) {
                return;
            }

            $model->syncVectorEmbeddingNow();

            Event::dispatch(new VectorSynced('model_embedding', [
                'model' => $model::class,
                'id' => $model->getKey(),
            ]));
        } catch (Throwable $e) {
            Event::dispatch(new VectorFailed('model_embedding', $e->getMessage(), [
                'model' => $model::class,
                'id' => $model->getKey(),
            ]));

            throw $e;
        }
    }
}
