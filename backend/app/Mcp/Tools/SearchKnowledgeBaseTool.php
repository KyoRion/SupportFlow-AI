<?php

namespace App\Mcp\Tools;

use App\Services\Knowledge\KnowledgeRetriever;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('search-knowledge-base')]
#[Description(
    'Search authorized support knowledge base documents. '
    .'Returns source identifiers and relevant document excerpts. '
    .'Read-only.'
)]
class SearchKnowledgeBaseTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Semantic search query.')
                ->required(),

            'limit' => $schema->integer()
                ->description('Maximum results, 1 to 10.'),
        ];
    }

    public function handle(
        Request $request,
        KnowledgeRetriever $retriever
    ): Response {
        $data = $request->validate([
            'query' => [
                'required', 'string', 'min:3', 'max:1000',
            ],
            'limit' => [
                'sometimes', 'integer', 'min:1', 'max:10',
            ],
        ]);

        $actor = $request->user();

        if (! $actor) {
            return Response::error('Authentication required.');
        }

        return Response::structured([
            'sources' => $retriever->search(
                $actor,
                $data['query'],
                $data['limit'] ?? 5
            ),
        ]);
    }
}
