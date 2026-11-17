<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeRetriever
{
    public function __construct(
        private readonly KnowledgeEmbedder $embedder
    ) {}

    public function search(
        User $actor,
        string $question,
        int $limit = 5
    ): array {
        $limit = max(1, min($limit, 10));

        $embedding = $this->embedder
            ->embedQuery($question);

        $chunks = KnowledgeChunk::query()
            ->select([
                'id',
                'knowledge_document_id',
                'chunk_index',
                'content',
            ])
            ->with('document:id,title,user_id,status')
            ->whereHas(
                'document',
                function (Builder $query) use ($actor) {
                    $query
                        ->where('user_id', $actor->id)
                        ->where('status', 'ready');
                }
            )
            ->whereVectorSimilarTo(
                'embedding',
                $embedding,
                minSimilarity: 0.35
            )
            ->limit($limit)
            ->get();

        return $chunks->map(fn (KnowledgeChunk $chunk) => [
            'source_id' => sprintf(
                'KB:%d:%d',
                $chunk->knowledge_document_id,
                $chunk->chunk_index
            ),
            'document_id' => $chunk->knowledge_document_id,
            'chunk_id' => $chunk->id,
            'title' => $chunk->document->title,
            'content' => $chunk->content,
        ])->all();
    }
}
