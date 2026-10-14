<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'products' => Product::count(),
                'pending_stores' => Store::where('status', StoreStatus::Pending->value)->count(),
                'pending_items' => OrderItem::where('fulfillment_status', 'pending')->count(),
                'low_stock' => Product::where('stock', '<=', Product::LOW_STOCK_THRESHOLD)->count(),
                'orders' => Order::count(),
                'sales_cents' => (int) Order::sum('total_cents'),
            ],
            'recent_orders' => Order::with('user:id,name')->latest('id')->limit(5)->get()
                ->map(fn (Order $order) => [
                    'number' => $order->number,
                    'customer' => $order->user?->name,
                    'status' => $order->status->value,
                    'total_cents' => $order->total_cents,
                ]),
            'stores' => Store::with('owner:id,name,email')->withCount('products')->orderBy('id')->get()
                ->map(fn (Store $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'owner' => $s->owner->name,
                    'email' => $s->owner->email,
                    'status' => $s->status->value,
                    'products_count' => $s->products_count,
                ]),
        ]);
    }

    public function updateStore(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(StoreStatus::class)],
        ]);

        $store->update($data);

        return back()->with('success', "{$store->name} is now {$data['status']}.");
    }
}
