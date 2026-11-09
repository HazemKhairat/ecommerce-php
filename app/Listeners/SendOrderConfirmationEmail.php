<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $customerEmail = $order->user->email;

        // Eager load items to prevent lazy evaluation inside view
        $order->load('items.variant');

        Mail::to($customerEmail)->send(new OrderConfirmationMail($order));
    }
}