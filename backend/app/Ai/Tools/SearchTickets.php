<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchTickets implements Tool
{
    public function __construct(
        private readonly User $actor
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search for customer support tickets. '
            .'Read-only. Use this tool when the user asks '
            .'for a list of tickets or wants to search for tickets.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->required(),
            'priority' => $schema->string()
                ->required(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(20)
                ->required(),
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $data = $request->validate([
            'status' => [
                'required',
                'string',
                'in:open,closed,pending',
            ],
            'priority' => [
                'required',
                'string',
                'in:low,medium,high,urgent',
            ],
            'limit' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        Log::info('AI tool invoked: SearchTickets', [
            'actor_id' => $this->actor->id,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'limit' => $data['limit'],
        ]);

        $tickets = Ticket::query()
            ->where('status', $data['status'])
            ->where('priority', $data['priority'])
            ->latest()
            ->limit($data['limit'])
            ->get([
                'id',
                'customer_id',
                'order_id',
                'status',
                'priority',
            ]);

        return json_encode([
            'tickets' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'customer_id' => $ticket->customer_id,
                    'order_number' => $ticket->order?->order_number,
                    'assignee' => $ticket->assignee?->name,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                ];
            }),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
