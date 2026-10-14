<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Orders', [
            'orders' => Order::with([
                'user:id,name,email',
                'items.store:id,name',
            ])
                ->latest('id')
                ->paginate(12)
                ->through(fn (Order $order) => [
                    'number' => $order->number,
                    'status' => $order->status->value,
                    'customer' => $order->user?->name,
                    'customer_email' => $order->user?->email,
                    'shipping_name' => $order->shipping_name,
                    'shipping_city' => $order->shipping_city,
                    'shipping_address' => $order->shipping_address,
                    'shipping_phone' => $order->shipping_phone,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                    'total_cents' => $order->total_cents,
                    'items' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'store' => $item->store?->name,
                        'fulfillment_status' => $item->fulfillment_status->value,
                        'line_cents' => $item->lineTotalCents(),
                    ])->values(),
                ]),
        ]);
    }
}
