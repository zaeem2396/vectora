<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Ingestion;

use Illuminate\Contracts\Foundation\Application;
use Vectora\Pinecone\Contracts\TextChunkingStrategy;
use Vectora\Pinecone\DTO\IngestedChunk;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Ingestion\Chunking\FixedSizeOverlappingChunker;
use Vectora\Pinecone\Ingestion\ExtractorRegistry;
use Vectora\Pinecone\Ingestion\Extractors\HtmlTextExtractor;
use Vectora\Pinecone\Ingestion\Http\GuzzleUrlReader;
use Vectora\Pinecone\Ingestion\IngestionPipeline;
use Vectora\Pinecone\Laravel\Jobs\IngestUpsertJob;
use Vectora\Pinecone\Laravel\PineconeManager;
use Vectora\Pinecone\Laravel\VectorStoreManager;

/**
 * Fluent entry for Phase 9 ingestion: {@see Vector::ingest()}.
 */
final class IngestionBuilder
{
    private string $text = '';

    /** @var array<string, mixed> */
    private array $baseMetadata = [];

    private ?TextChunkingStrategy $chunker = null;

    /** @var (callable(IngestedChunk): IngestedChunk)|null */
    private $enrich = null;

    public function __construct(
        private readonly Application $app,
        private readonly ExtractorRegistry $extractors,
        private readonly IngestionPipeline $pipeline,
        private readonly GuzzleUrlReader $urlReader,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function fromString(string $text, array $metadata = []): self
    {
        $this->text = $text;
        $this->baseMetadata = array_merge($this->baseMetadata, $metadata);

        return $this;
    }

    /**
     * Reads a local file using {@see ExtractorRegistry} (txt, md, html, docx, pdf, …).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function fromPath(string $path, array $metadata = []): self
    {
        $this->text = $this->extractors->extractFromPath($path);
        $this->baseMetadata = array_merge($this->baseMetadata, $metadata, [
            'source_path' => $path,
        ]);

        return $this;
    }

    /**
     * Fetches a URL and extracts text (HTML is stripped; otherwise treated as plain text).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function fromUrl(string $url, array $metadata = []): self
    {
        $raw = $this->urlReader->getBodyString($url);
        $html = $this->app->make(HtmlTextExtractor::class);
        $text = str_starts_with(ltrim($raw), '<') ? $html->htmlToText($raw) : trim($raw);
        $this->text = $text;
        $this->baseMetadata = array_merge($this->baseMetadata, $metadata, [
            'source_url' => $url,
        ]);

        return $this;
    }

    /**
     * @param  (callable(IngestedChunk): IngestedChunk)  $callback
     */
    public function enrich(callable $callback): self
    {
        $this->enrich = $callback;

        return $this;
    }

    public function chunks(int $size = 512, int $overlap = 64): self
    {
        $this->chunker = new FixedSizeOverlappingChunker($size, $overlap);

        return $this;
    }

    public function chunkUsing(TextChunkingStrategy $strategy): self
    {
        $this->chunker = $strategy;

        return $this;
    }

    /**
     * Embeds and upserts immediately (same process).
     *
     * @return int Number of vectors upserted
     */
    public function syncUpsert(
        string $vectorIdPrefix,
        ?string $index = null,
        ?string $namespace = null,
    ): int {
        $chunker = $this->chunker ?? new FixedSizeOverlappingChunker(
            (int) $this->app['config']->get('pinecone.ingestion.default_chunk_size', 512),
            (int) $this->app['config']->get('pinecone.ingestion.default_chunk_overlap', 64),
        );
        $chunks = $this->pipeline->run($this->text, $chunker, $this->enrich, $this->baseMetadata);
        if ($chunks === []) {
            return 0;
        }

        /** @var PineconeManager $mgr */
        $mgr = $this->app->make('vectora.pinecone');
        $texts = array_map(static fn (IngestedChunk $c): string => $c->text, $chunks);
        $vectors = $mgr->embedMany($texts);
        $records = [];
        foreach ($chunks as $i => $chunk) {
            $records[] = new VectorRecord(
                $vectorIdPrefix.'-'.$chunk->index,
                $vectors[$i],
                $chunk->metadata !== [] ? $chunk->metadata : null,
            );
        }
        $request = new UpsertVectorsRequest($records, $namespace);
        $this->app->make(VectorStoreManager::class)->driver(null, $index)->upsert($request);

        return count($records);
    }

    /** Queues embed + upsert via {@see IngestUpsertJob}. */
    public function dispatchUpsert(
        string $vectorIdPrefix,
        ?string $index = null,
        ?string $namespace = null,
    ): void {
        $chunker = $this->chunker ?? new FixedSizeOverlappingChunker(
            (int) $this->app['config']->get('pinecone.ingestion.default_chunk_size', 512),
            (int) $this->app['config']->get('pinecone.ingestion.default_chunk_overlap', 64),
        );
        $chunks = $this->pipeline->run($this->text, $chunker, $this->enrich, $this->baseMetadata);
        if ($chunks === []) {
            return;
        }

        $payload = [];
        foreach ($chunks as $chunk) {
            $payload[] = [
                'text' => $chunk->text,
                'metadata' => $chunk->metadata,
            ];
        }

        IngestUpsertJob::dispatch($payload, $vectorIdPrefix, $index, $namespace);
    }
}
