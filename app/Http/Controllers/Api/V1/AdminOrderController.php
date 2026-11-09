<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    // List all store orders with optional status filter
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $orders = $query->paginate(15);

        return response()->json($orders);
    }

    // View specific order details for admin
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => $order->load('items'),
        ]);
    }

    // Update order lifecycle status
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order->update([
            'status' => $request->validated('status'),
        ]);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => $order,
        ]);
    }
}