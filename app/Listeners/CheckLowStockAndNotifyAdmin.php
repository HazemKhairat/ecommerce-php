<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Support\Facades\Notification;

class CheckLowStockAndNotifyAdmin
{
    // Threshold below which an alert is triggered
    public const LOW_STOCK_THRESHOLD = 5;

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        // Fetch all store administrators
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        // Loop through each purchased item in the order
        foreach ($order->items as $item) {
            $variant = $item->variant;

            if (!$variant) {
                continue;
            }

            // Check if current remaining stock is at or below the threshold
            if ($variant->stock <= self::LOW_STOCK_THRESHOLD) {
                Notification::send($admins, new LowStockAlertNotification($variant, $variant->stock));
            }
        }
    }
}