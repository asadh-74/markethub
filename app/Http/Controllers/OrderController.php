<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()->latest('id')->paginate(10)
            ->through(fn (Order $o) => [
                'number' => $o->number,
                'status' => $o->status->value,
                'total_cents' => $o->total_cents,
                'created_at' => $o->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->load('items.store:id,name');

        return Inertia::render('Orders/Show', [
            'order' => [
                'number' => $order->number,
                'status' => $order->status->value,
                'total_cents' => $order->total_cents,
                'created_at' => $order->created_at->toDateTimeString(),
                'shipping' => [
                    'name' => $order->shipping_name,
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'phone' => $order->shipping_phone,
                ],
                'items' => $order->items->map(fn (OrderItem $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'store' => $i->store?->name,
                    'quantity' => $i->quantity,
                    'unit_price_cents' => $i->unit_price_cents,
                    'line_cents' => $i->lineTotalCents(),
                    'fulfillment_status' => $i->fulfillment_status->value,
                ])->values(),
            ],
        ]);
    }
}
