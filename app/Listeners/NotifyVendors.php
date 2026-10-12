<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyVendors implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $items = $event->order->items()->with('store.owner')->get();

        // One notification per seller, covering only that seller's items.
        foreach ($items->groupBy('store_id') as $storeItems) {
            $owner = $storeItems->first()->store->owner;

            $owner->notify(new NewOrderNotification($event->order, $storeItems->sum('quantity')));
        }
    }
}
