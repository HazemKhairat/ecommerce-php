<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $cart,
        ]);
    }

    // Add variant to cart with stock validation
    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $user = $request->user();
        $variantId = $request->validated('product_variant_id');
        $requestedQuantity = $request->validated('quantity');

        $variant = ProductVariant::findOrFail($variantId);

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Check if item already exists in cart to calculate total requested
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();
        $currentCartQuantity = $existingItem ? $existingItem->quantity : 0;
        $totalQuantity = $currentCartQuantity + $requestedQuantity;

        // Stock availability validation
        if ($totalQuantity > $variant->stock) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'available_stock' => $variant->stock,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $totalQuantity,
            ]);
            $item = $existingItem;
        } else {
            $item = $cart->items()->create([
                'product_variant_id' => $variantId,
                'quantity' => $requestedQuantity,
                'unit_price' => $variant->price,
            ]);
        }

        return response()->json([
            'message' => 'Item added to cart successfully.',
            'data' => $item,
        ], Response::HTTP_CREATED);
    }

    // Update quantity of a specific cart item
    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cart = $request->user()->cart;
        
        // Ensure user owns this cart item
        if (!$cart || $cartItem->cart_id !== $cart->id) {
            return response()->json([
                'message' => 'Unauthorized action on cart item.',
            ], Response::HTTP_FORBIDDEN);
        }

        $newQuantity = $request->validated('quantity');
        $variant = $cartItem->variant;

        // Stock availability validation
        if ($newQuantity > $variant->stock) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'available_stock' => $variant->stock,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->update([
            'quantity' => $newQuantity,
        ]);

        return response()->json([
            'message' => 'Cart item updated successfully.',
            'data' => $cartItem,
        ]);
    }

    // Remove single item from cart
    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $request->user()->cart;

        if (!$cart || $cartItem->cart_id !== $cart->id) {
            return response()->json([
                'message' => 'Unauthorized action on cart item.',
            ], Response::HTTP_FORBIDDEN);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart successfully.',
        ]);
    }

    // Clear all items from cart
    public function clear(Request $request): JsonResponse
    {
        $cart = $request->user()->cart;

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'message' => 'Cart cleared successfully.',
        ]);
    }
}