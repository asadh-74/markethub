<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification
{
    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order {$this->order->number} confirmed")
            ->greeting("Thanks for your order, {$notifiable->name}!")
            ->line("Your order {$this->order->number} totalling Rs ".number_format($this->order->total_cents / 100, 2).' has been paid.')
            ->line('Each seller will ship their items separately.');
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => "Order {$this->order->number} confirmed", 'order' => $this->order->number];
    }
}
