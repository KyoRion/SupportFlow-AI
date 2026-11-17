<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Mcp\Servers\SupportFlowServer;
use App\Mcp\Tools\GetTicketDetailsTool;
use App\Mcp\Tools\SearchTicketsTool;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportFlowMcpTest extends TestCase
{
    use RefreshDatabase;

    private function createTicket(User $owner): Ticket
    {
        $customer = Customer::create([
            'name' => 'MCP Test Customer',
            'email' => 'mcp-customer@example.test',
        ]);

        return Ticket::create([
            'customer_id' => $customer->id,
            'created_by' => $owner->id,
            'subject' => 'Delayed order delivery',
            'description' => 'Customer has not received the order.',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);
    }

    public function test_owner_can_read_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->createTicket($owner);

        SupportFlowServer::actingAs($owner)
            ->tool(GetTicketDetailsTool::class, [
                'ticket_id' => $ticket->id,
            ])
            ->assertOk()
            ->assertSee('Delayed order delivery');
    }

    public function test_other_user_cannot_read_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ticket = $this->createTicket($owner);

        SupportFlowServer::actingAs($other)
            ->tool(GetTicketDetailsTool::class, [
                'ticket_id' => $ticket->id,
            ])
            ->assertHasErrors();
    }

    public function test_owner_can_search_tickets(): void
    {
        $owner = User::factory()->create();
        $this->createTicket($owner);

        SupportFlowServer::actingAs($owner)
            ->tool(SearchTicketsTool::class, [
                'status' => 'open',
                'priority' => 'high',
                'limit' => 5,
            ])
            ->assertOk()
            ->assertSee('Delayed order delivery');
    }
}
