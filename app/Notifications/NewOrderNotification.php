<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    public function __construct(public Order $order, public int $itemCount)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order {$this->order->number}")
            ->line("You have {$this->itemCount} item(s) to ship in order {$this->order->number}.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "New order {$this->order->number}: {$this->itemCount} item(s) to ship",
            'order' => $this->order->number,
        ];
    }
}
