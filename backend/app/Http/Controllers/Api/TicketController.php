<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $tickets = Ticket::query()
            ->with(['customer:id,name,email', 'assignee:id,name'])
            ->when(
                $filters['status'] ?? null,
                fn ($query, $value) => $query->where('status', $value)
            )
            ->when(
                $filters['priority'] ?? null,
                fn ($query, $value) => $query->where('priority', $value)
            )
            ->when(
                $filters['search'] ?? null,
                fn ($query, $value) => $query->where(
                    'subject', 'ilike', "%{$value}%"
                )
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function show(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        return new TicketResource($ticket->load([
            'customer',
            'order',
            'assignee',
            'messages.author',
        ]));
    }

    public function store(StoreTicketRequest $request)
    {
        Gate::authorize('create', Ticket::class);

        $ticket = $this->tickets->create(
            $request->user(),
            $request->validated()
        );

        return (new TicketResource(
            $ticket->load(['customer', 'order', 'assignee'])
        ))->response()->setStatusCode(201);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket
    ) {
        Gate::authorize('update', $ticket);

        $updated = $this->tickets->update(
            $ticket,
            $request->validated()
        );

        return new TicketResource(
            $updated->load(['customer', 'order', 'assignee'])
        );
    }
}
