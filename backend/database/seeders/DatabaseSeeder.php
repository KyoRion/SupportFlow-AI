<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::firstOrCreate(
            ['email' => 'agent@supportflow.test'],
            [
                'name' => 'Demo Support Agent',
                'password' => Hash::make(Str::random(48)),
            ]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'nguyenvana@example.test'],
            [
                'name' => 'Nguyen Van A',
                'phone' => '0900000000',
            ]
        );

        $order = Order::firstOrCreate(
            ['order_number' => 'ORD-1024'],
            [
                'customer_id' => $customer->id,
                'total_amount' => '1490000.00',
                'currency' => 'VND',
                'status' => 'shipping',
                'placed_at' => now()->subDays(7),
            ]
        );

        $ticket = Ticket::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'subject' => 'Delayed delivery ORD-1024',
            ],
            [
                'order_id' => $order->id,
                'created_by' => $agent->id,
                'assigned_to' => $agent->id,
                'description' => 'Order has not arrived after 7 days.',
                'status' => TicketStatus::Open,
                'priority' => TicketPriority::High,
            ]
        );

        $ticket->messages()->firstOrCreate(
            ['body' => 'Ticket received. Investigating delivery.'],
            [
                'author_id' => $agent->id,
                'is_internal' => true,
            ]
        );
    }
}
