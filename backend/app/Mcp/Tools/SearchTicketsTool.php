<?php

namespace App\Mcp\Tools;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Services\Tickets\TicketReader;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('search-tickets')]
#[Description(
    'Search support tickets visible to the authenticated user.
     Optionally filter by status and priority.
     Returns at most 20 tickets. Read-only.'
)]
class SearchTicketsTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum([
                    'open',
                    'in_progress',
                    'resolved',
                    'closed',
                ])
                ->description('Optional ticket status filter.'),

            'priority' => $schema->string()
                ->enum([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ])
                ->description('Optional priority filter.'),

            'limit' => $schema->integer()
                ->description('Maximum number of results (1-20).')
                ->default(10),
        ];
    }

    public function handle(
        Request $request,
        TicketReader $reader
    ): Response|ResponseFactory {
        $validated = $request->validate([
            'status' => [
                'sometimes',
                Rule::enum(TicketStatus::class),
            ],
            'priority' => [
                'sometimes',
                Rule::enum(TicketPriority::class),
            ],
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        $actor = $request->user();

        if (! $actor) {
            return Response::error('Authentication required.');
        }

        $results = $reader->searchFor(
            $actor,
            $validated
        );

        return Response::structured($results);
    }
}
