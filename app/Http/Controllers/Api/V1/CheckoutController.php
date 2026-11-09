<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;


class CheckoutController extends Controller
{
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $cart = $user->cart;
        // return response()->json([
        //     "cart" => $cart,
        //     "items" => $cart->items()->count()
        // ]);
        // 1. Validate that the cart exists and is not empty
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json([
                'message' => 'Cart is empty. Add items before checkout.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Fetch shipping address snapshot
        $address = Address::findOrFail($request->validated('address_id'));
        $shippingSnapshot = [
            'recipient_name' => $address->recipient_name,
            'phone_number' => $address->phone_number,
            'city' => $address->city,
            'street_address' => $address->street_address,
            'postal_code' => $address->postal_code,
        ];

        try {
            // 3. Execute atomic transaction (Baseline: Standard transaction without lockForUpdate)
            $order = DB::transaction(function () use ($cart, $user, $shippingSnapshot) {
                $subtotal = 0;
                $orderItemsData = [];

                // Re-verify stock and calculate frozen prices
                foreach ($cart->items as $cartItem) {
                    $variant = ProductVariant::findOrFail($cartItem->product_variant_id);

                    // Check if stock is still sufficient
                    if ($cartItem->quantity > $variant->stock) {
                        throw new Exception("Product variant [{$variant->sku}] has insufficient stock.");
                    }

                    $itemSubtotal = round($cartItem->quantity * $variant->price, 2);
                    $subtotal += $itemSubtotal;

                    $orderItemsData[] = [
                        'variant' => $variant,
                        'variant_id' => $variant->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $variant->price,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $shippingCost = 0.00; // Flat or free shipping in baseline
                $totalAmount = $subtotal + $shippingCost;

                // Create Immutable Order Record
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'shipping_address' => $shippingSnapshot,
                ]);

                // Create Order Items & Deduct Inventory Stock
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $itemData['variant_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    // Deduct catalog stock
                    $itemData['variant']->decrement('stock', $itemData['quantity']);
                }

                // Empty user's cart items
                $cart->items()->delete();

                return $order;
            });
            
            OrderPlaced::dispatch($order);
            return response()->json([
                'message' => 'Order placed successfully.',
                'data' => $order->load('items'),
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}