<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
                'orders' => Order::count(),
                'sales_cents' => (int) Order::sum('total_cents'),
            ],
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
