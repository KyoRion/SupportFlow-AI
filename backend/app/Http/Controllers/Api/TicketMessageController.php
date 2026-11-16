<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Resources\TicketMessageResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Support\Facades\Gate;

class TicketMessageController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets
    ) {}

    public function store(
        StoreTicketMessageRequest $request,
        Ticket $ticket
    ) {
        Gate::authorize('update', $ticket);

        $message = $this->tickets->addMessage(
            $ticket,
            $request->user(),
            $request->validated()
        );

        return (new TicketMessageResource(
            $message->load('author')
        ))->response()->setStatusCode(201);
    }
}
