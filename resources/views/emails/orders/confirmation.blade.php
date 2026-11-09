<x-mail::message>
# Order Confirmation

Thank you for your order, **{{ $order->shipping_address['recipient_name'] }}**!

Your order **#{{ $order->order_number }}** has been placed successfully and is currently being processed.

### Order Summary:
<x-mail::table>
| Item | Qty | Unit Price | Subtotal |
| :--- | :-- | :--------- | :------- |
@foreach ($order->items as $item)
| {{ $item->variant->sku ?? 'Item #' . $item->product_variant_id }} | {{ $item->quantity }} | ${{ number_format($item->unit_price, 2) }} | ${{ number_format($item->subtotal, 2) }} |
@endforeach
</x-mail::table>

**Subtotal:** ${{ number_format($order->subtotal, 2) }}  
**Shipping:** ${{ number_format($order->shipping_cost, 2) }}  
**Total Amount:** ${{ number_format($order->total_amount, 2) }}  

### Shipping To:
{{ $order->shipping_address['street_address'] }}, {{ $order->shipping_address['city'] }}  
**Phone:** {{ $order->shipping_address['phone_number'] }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>