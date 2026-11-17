<?php

namespace App\Mcp\Tools;

use App\Services\Tickets\TicketReader;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get-ticket-details')]
#[Description(
    'Retrieve the details of a support ticket by its numeric ID.
     Read-only. Returns data only when the authenticated
     user is authorized to view the ticket.'
)]
class GetTicketDetailsTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->description('Numeric ID of the support ticket.')
                ->required(),
        ];
    }

    public function handle(
        Request $request,
        TicketReader $reader
    ): Response|ResponseFactory {
        $validated = $request->validate([
            'ticket_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $actor = $request->user();

        if (! $actor) {
            return Response::error('Authentication required.');
        }

        $ticket = $reader->detailsFor(
            $actor,
            (int) $validated['ticket_id']
        );

        if (! $ticket) {
            return Response::error('Ticket not available.');
        }

        return Response::structured([
            'ticket' => $ticket,
        ]);
    }
}
