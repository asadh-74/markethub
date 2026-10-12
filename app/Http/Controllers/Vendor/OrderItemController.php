<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function ship(Request $request, OrderItem $item): RedirectResponse
    {
        $store = $request->user()->store;

        abort_unless($store !== null && $item->store_id === $store->id, 403);

        $item->update(['fulfillment_status' => FulfillmentStatus::Shipped->value]);

        // When every seller has shipped their part, the whole order is shipped.
        $order = $item->order;
        $allShipped = ! $order->items()->where('fulfillment_status', '!=', FulfillmentStatus::Shipped->value)->exists();

        if ($allShipped) {
            $order->update(['status' => OrderStatus::Shipped->value]);
        }

        return back()->with('success', 'Marked as shipped.');
    }
}
