<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function customers(): JsonResponse
    {
        $customers = Customer::query()
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return response()->json($customers);
    }

    public function orders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
        ]);

        $orders = Order::query()
            ->where('customer_id', $data['customer_id'])
            ->latest()
            ->limit(50)
            ->get([
                'id',
                'customer_id',
                'order_number',
            ]);

        return response()->json($orders);
    }
}
