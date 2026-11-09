<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProductVariant $variant,
        public int $remainingStock
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('⚠️ Low Stock Alert: ' . $this->variant->sku)
            ->greeting('Attention Store Admin,')
            ->line("The inventory for product variant **{$this->variant->sku}** has dropped below the safety threshold.")
            ->line("**Remaining Units:** {$this->remainingStock}")
            ->line("**Unit Price:** $" . number_format((float) $this->variant->price, 2))
            ->action('View Catalog', url('/api/v1/products'))
            ->line('Please restock this item promptly to prevent stockouts.');
    }
}