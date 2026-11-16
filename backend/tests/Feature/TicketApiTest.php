<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
        ]);
    }

    private function createTicket(User $user): Ticket
    {
        return Ticket::create([
            'customer_id' => $this->createCustomer()->id,
            'created_by' => $user->id,
            'subject' => 'Test delivery issue',
            'description' => 'Customer reports delayed shipping.',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
        ]);
    }

    public function test_guest_cannot_list_tickets(): void
    {
        $this->getJson('/api/tickets')
            ->assertUnauthorized();
    }

    public function test_agent_can_create_ticket(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tickets', [
            'customer_id' => $customer->id,
            'subject' => 'Delayed delivery',
            'description' => 'Order has not arrived.',
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('tickets', [
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'status' => 'open',
        ]);
    }

    public function test_order_must_belong_to_customer(): void
    {
        $user = User::factory()->create();

        $customerA = $this->createCustomer();

        $customerB = Customer::create([
            'name' => 'Another Customer',
            'email' => 'another@example.test',
        ]);

        $order = Order::create([
            'customer_id' => $customerB->id,
            'order_number' => 'ORD-9999',
            'total_amount' => '100000.00',
            'currency' => 'VND',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/tickets', [
            'customer_id' => $customerA->id,
            'order_id' => $order->id,
            'subject' => 'Invalid customer order',
            'description' => 'This order belongs to someone else.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');
    }

    public function test_cannot_skip_ticket_status(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket($user);

        Sanctum::actingAs($user);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'resolved',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }

    public function test_other_agent_cannot_update_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ticket = $this->createTicket($owner);

        Sanctum::actingAs($other);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ])->assertForbidden();
    }
}
