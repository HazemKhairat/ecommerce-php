<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    // List customer orders (Baseline: No eager loading)
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    // View specific order details with items
    public function show(Request $request, Order $order): JsonResponse
    {
        // Prevent IDOR: Ensure order belongs to logged-in customer
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to order.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Return order with its snapshot items
        return response()->json([
            'data' => $order->load('items'),
        ]);
    }
}