<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TicketReader
{
    public function detailsFor(
        User $actor,
        int $ticketId
    ): ?array {
        $ticket = Ticket::query()
            ->visibleTo($actor)
            ->with([
                'customer:id,name',
                'order:id,order_number,status',
                'assignee:id,name',
            ])
            ->find($ticketId);

        if (! $ticket) {
            return null;
        }

        Gate::forUser($actor)->authorize('view', $ticket);

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => mb_strimwidth(
                $ticket->description,
                0,
                2000,
                '...'
            ),
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'customer' => $ticket->customer?->name,
            'order_number' => $ticket->order?->order_number,
            'order_status' => $ticket->order?->status,
            'assignee' => $ticket->assignee?->name,
            'resolved_at' => $ticket->resolved_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
        ];
    }

    public function searchFor(
        User $actor,
        array $filters
    ): array {
        Gate::forUser($actor)->authorize(
            'viewAny',
            Ticket::class
        );

        $limit = $filters['limit'] ?? 10;

        $tickets = Ticket::query()
            ->visibleTo($actor)
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['priority'] ?? null,
                fn ($query, $priority) => $query->where('priority', $priority)
            )
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'count' => $tickets->count(),
            'tickets' => $tickets->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'created_at' => $ticket->created_at?->toISOString(),
            ])->all(),
        ];
    }
}
