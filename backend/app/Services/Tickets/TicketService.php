<?php

namespace App\Services\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function create(User $actor, array $data): Ticket
    {
        return Ticket::create([
            ...$data,
            'created_by' => $actor->id,
            'status' => TicketStatus::Open,
            'priority' => $data['priority']
                ?? TicketPriority::Medium,
        ]);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $locked = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            if (array_key_exists('status', $data)) {
                $next = TicketStatus::from($data['status']);

                if ($locked->status !== $next) {
                    if (! $locked->status->canTransitionTo($next)) {
                        throw ValidationException::withMessages([
                            'status' => [
                                'Invalid ticket status transition.',
                            ],
                        ]);
                    }

                    $locked->status = $next;

                    if ($next === TicketStatus::Resolved) {
                        $locked->resolved_at = now();
                    } elseif ($next === TicketStatus::InProgress) {
                        $locked->resolved_at = null;
                    }
                }
            }

            if (array_key_exists('priority', $data)) {
                $locked->priority = TicketPriority::from(
                    $data['priority']
                );
            }

            if (array_key_exists('assigned_to', $data)) {
                $locked->assigned_to = $data['assigned_to'];
            }

            $locked->save();

            return $locked->refresh();
        });
    }

    public function addMessage(
        Ticket $ticket,
        User $actor,
        array $data
    ): TicketMessage {
        return DB::transaction(function () use (
            $ticket,
            $actor,
            $data
        ) {
            $locked = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            if ($locked->status === TicketStatus::Closed) {
                throw ValidationException::withMessages([
                    'ticket' => [
                        'Cannot add messages to a closed ticket.',
                    ],
                ]);
            }

            return $locked->messages()->create([
                'author_id' => $actor->id,
                'body' => $data['body'],
                'is_internal' => $data['is_internal'] ?? false,
            ]);
        });
    }
}
