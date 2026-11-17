<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\Knowledge\KnowledgeEmbedder;
use App\Services\Knowledge\TextChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class IndexKnowledgeDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $documentId
    ) {
        $this->onQueue('knowledge');
    }

    public function handle(
        TextChunker $chunker,
        KnowledgeEmbedder $embedder
    ): void {
        $document = KnowledgeDocument::findOrFail(
            $this->documentId
        );

        if ($document->status === 'ready') {
            return;
        }

        $document->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $chunks = $chunker->split($document->content);

        if ($chunks === [] || count($chunks) > 100) {
            throw new RuntimeException(
                'Invalid document chunk count.'
            );
        }

        // External API call must stay outside the DB transaction.
        $vectors = $embedder->embedMany($chunks);

        DB::transaction(function () use (
            $document,
            $chunks,
            $vectors
        ) {
            $locked = KnowledgeDocument::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            $locked->chunks()->delete();

            foreach ($chunks as $index => $content) {
                $locked->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $content,
                    'embedding' => $vectors[$index],
                ]);
            }

            $locked->update([
                'status' => 'ready',
                'chunk_count' => count($chunks),
                'error_message' => null,
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        KnowledgeDocument::query()
            ->whereKey($this->documentId)
            ->update([
                'status' => 'failed',
                'error_message' =>
                    'Indexing failed. Check server logs.',
            ]);
    }
}
