<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTicketDetails implements Tool
{
    public function __construct(
        private readonly User $actor
    ) {}

    public function description(): Stringable|string
    {
        return 'Retrieve real ticket information by ticket ID. '
            .'Read-only. Use this tool when the user asks '
            .'about a specific support ticket.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->min(1)
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->validate([
            'ticket_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        Log::info('AI tool invoked: GetTicketDetails', [
            'actor_id' => $this->actor->id,
            'ticket_id' => $data['ticket_id'],
        ]);

        $ticket = Ticket::query()
            ->with([
                'customer:id,name',
                'order:id,order_number,status',
                'assignee:id,name',
            ])
            ->find($data['ticket_id']);

        if (
            ! $ticket ||
            Gate::forUser($this->actor)
                ->denies('view', $ticket)
        ) {
            return json_encode([
                'error' => 'Ticket not available.',
            ], JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'customer' => $ticket->customer?->name,
            'order_number' => $ticket->order?->order_number,
            'order_status' => $ticket->order?->status,
            'assignee' => $ticket->assignee?->name,
            'resolved_at' => $ticket->resolved_at?->toISOString(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
