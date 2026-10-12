<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    public function __construct(public Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->line("Only {$this->product->stock} left of {$this->product->name}. Time to restock.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Low stock: {$this->product->name} ({$this->product->stock} left)",
            'product' => $this->product->slug,
        ];
    }
}
