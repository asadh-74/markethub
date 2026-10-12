<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckLowStock implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $productIds = $event->order->items()->pluck('product_id')->filter();

        Product::with('store.owner')
            ->whereIn('id', $productIds)
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->get()
            ->each(fn (Product $product) => $product->store->owner->notify(new LowStockNotification($product)));
    }
}
