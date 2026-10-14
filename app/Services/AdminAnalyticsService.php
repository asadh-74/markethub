<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminAnalyticsService
{
    /**
     * Real database aggregates, never generated revenue.
     * Money here is simulated: checkout does not process actual payments.
     */
    public function dashboard(int $days): array
    {
        $today = Carbon::today();
        $start = $today->copy()->subDays($days - 1)->startOfDay();

        $paidStatuses = [OrderStatus::Paid->value, OrderStatus::Shipped->value];
        // DATE() is supported by SQLite, MySQL and PostgreSQL. Group in SQL
        // so larger order volumes do not get loaded into PHP memory.
        $rows = Order::query()
            ->whereIn('status', $paidStatuses)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $today->copy()->addDay()->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as order_count, COALESCE(SUM(total_cents), 0) as simulated_sales_cents')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $trend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $rows): array {
            $date = $start->copy()->addDays($offset);
            $day = $date->toDateString();
            $row = $rows->get($day);

            return [
                'date' => $day,
                'label' => $date->format('M j'),
                'order_count' => (int) ($row?->order_count ?? 0),
                'simulated_sales_cents' => (int) ($row?->simulated_sales_cents ?? 0),
            ];
        })->values();

        $inventory = Category::query()
            ->withCount(['products as visible_products' => fn ($q) => $q->listed()])
            ->withSum(['products as units_in_stock' => fn ($q) => $q->listed()], 'stock')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'product_count' => (int) $category->visible_products,
                'stock_units' => (int) ($category->units_in_stock ?? 0),
            ])
            ->values();

        $lowStock = Product::listed()
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->with('store:id,name')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'slug' => $product->slug,
                'stock' => $product->stock,
                'store' => $product->store?->name,
            ])
            ->values();

        return [
            'period_days' => $days,
            'trend' => $trend->all(),
            'inventory' => $inventory->all(),
            'low_stock_products' => $lowStock->all(),
            'period_orders' => (int) $trend->sum('order_count'),
            'period_sales_cents' => (int) $trend->sum('simulated_sales_cents'),
            'stock_units' => (int) $inventory->sum('stock_units'),
            'visible_products' => (int) $inventory->sum('product_count'),
        ];
    }
}
