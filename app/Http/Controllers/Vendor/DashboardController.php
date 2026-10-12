<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $store = $user->store;

        abort_if($store === null, 403, 'No store is attached to this account.');

        $items = OrderItem::where('store_id', $store->id);

        return Inertia::render('Vendor/Dashboard', [
            'store' => ['name' => $store->name, 'status' => $store->status->value],
            'stats' => [
                'revenue_cents' => (int) (clone $items)->selectRaw('COALESCE(SUM(unit_price_cents * quantity), 0) as total')->value('total'),
                'pending_items' => (clone $items)->where('fulfillment_status', FulfillmentStatus::Pending->value)->count(),
                'products' => Product::where('store_id', $store->id)->count(),
            ],
            'low_stock' => Product::where('store_id', $store->id)
                ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
                ->orderBy('stock')->limit(5)
                ->get(['id', 'name', 'slug', 'stock']),
            'recent_items' => OrderItem::with('order:id,number,shipping_name,created_at')
                ->where('store_id', $store->id)
                ->latest('id')->limit(10)->get()
                ->map(fn (OrderItem $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'quantity' => $i->quantity,
                    'line_cents' => $i->lineTotalCents(),
                    'order_number' => $i->order->number,
                    'customer' => $i->order->shipping_name,
                    'fulfillment_status' => $i->fulfillment_status->value,
                ]),
            'notifications' => $user->notifications()->latest()->limit(6)->get()
                ->map(fn ($n) => ['id' => $n->id, 'message' => $n->data['message'] ?? '', 'unread' => $n->read_at === null]),
        ]);
    }
}
