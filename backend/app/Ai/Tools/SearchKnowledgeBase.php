<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\Knowledge\KnowledgeRetriever;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchKnowledgeBase implements Tool
{
    public function __construct(
        private readonly User $actor,
        private readonly KnowledgeRetriever $retriever
    ) {}

    public function description(): Stringable|string
    {
        return 'Search authorized knowledge base documents '
            .'for support policies, delivery procedures, '
            .'refund rules, and customer support guidance. '
            .'Use when answering policy or procedure questions. '
            .'Returns factual excerpts and source identifiers.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Question to search for.')
                ->required(),

            'limit' => $schema->integer()
                ->description('Maximum number of results, 1 to 10.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->validate([
            'query' => [
                'required', 'string', 'min:3', 'max:1000',
            ],
            'limit' => [
                'sometimes', 'integer', 'min:1', 'max:10',
            ],
        ]);

        $sources = $this->retriever->search(
            $this->actor,
            $data['query'],
            $data['limit'] ?? 5
        );

        return json_encode([
            'sources' => $sources,
            'count' => count($sources),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
