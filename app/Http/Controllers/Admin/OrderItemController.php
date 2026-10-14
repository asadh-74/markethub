<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    public function ship(OrderItem $item): RedirectResponse
    {
        DB::transaction(function () use ($item) {
            $order = $item->order()->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($order->status, [OrderStatus::Paid, OrderStatus::Shipped], true),
                422,
                'Only paid demo orders can be shipped.'
            );

            $item->update(['fulfillment_status' => FulfillmentStatus::Shipped->value]);

            if (! $order->items()
                ->where('fulfillment_status', '!=', FulfillmentStatus::Shipped->value)
                ->exists()) {
                $order->update(['status' => OrderStatus::Shipped->value]);
            }
        });

        return back()->with('success', 'Order item marked as shipped.');
    }
}
