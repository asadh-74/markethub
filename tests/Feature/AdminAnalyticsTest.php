<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class AdminAnalyticsTest extends MarketplaceTestCase
{
    public function test_only_admins_can_see_analytics(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();
        $this->actingAs($this->store()->owner)->get('/admin')->assertForbidden();
    }

    public function test_zero_sales_produces_true_zero_daily_values_and_category_stock(): void
    {
        $visibleStore = $this->store('approved');
        $this->product($visibleStore, ['stock' => 12, 'category_id' => null]);
        $this->product($this->store('pending'), ['stock' => 99]);

        $this->actingAs($this->admin())->get('/admin?period=7')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('analytics.period_days', 7)
                ->has('analytics.trend', 7)
                ->where('analytics.period_orders', 0)
                ->where('analytics.period_sales_cents', 0)
                ->where('analytics.visible_products', 1)
                ->where('analytics.stock_units', 0)
                ->where('stats.orders', 0)
                ->where('stats.sales_cents', 0)
                ->etc());
    }

    public function test_analytics_respect_date_range_and_exclude_pending_from_sales(): void
    {
        $buyer = $this->customer();
        $p = $this->product(null, ['stock' => 4]);
        $today = $this->orderWith($buyer, [[$p, 1]]);
        $today->update(['subtotal_cents' => 8800, 'total_cents' => 8800]);

        $older = $this->orderWith($buyer, [[$p, 1]]);
        $older->update([
            'subtotal_cents' => 5000,
            'total_cents' => 5000,
            'created_at' => Carbon::today()->subDays(9)->setTime(12, 0),
            'updated_at' => Carbon::today()->subDays(9)->setTime(12, 0),
        ]);

        $pending = $this->orderWith($buyer, [[$p, 1]]);
        $pending->update([
            'status' => OrderStatus::Pending->value,
            'subtotal_cents' => 98000,
            'total_cents' => 98000,
        ]);

        $this->actingAs($this->admin())->get('/admin?period=7')
            ->assertInertia(fn (Assert $page) => $page
                ->where('analytics.period_orders', 1)
                ->where('analytics.period_sales_cents', 8800)
                ->where('analytics.trend.6.order_count', 1)
                ->where('analytics.trend.6.simulated_sales_cents', 8800)
                ->where('stats.sales_cents', 13800)
                ->etc());

        $this->get('/admin?period=30')
            ->assertInertia(fn (Assert $page) => $page
                ->where('analytics.period_orders', 2)
                ->where('analytics.period_sales_cents', 13800)
                ->has('analytics.trend', 30)
                ->etc());
    }

    public function test_stocks_include_only_active_products_from_approved_vendors(): void
    {
        $category = \App\Models\Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $approved = $this->store('approved');
        $this->product($approved, ['category_id' => $category->id, 'stock' => 3, 'is_active' => true, 'name' => 'Visible low item']);
        $this->product($approved, ['category_id' => $category->id, 'stock' => 9, 'is_active' => false]);
        $this->product($this->store('pending'), ['category_id' => $category->id, 'stock' => 100, 'is_active' => true]);

        $this->actingAs($this->admin())->get('/admin')->assertInertia(fn (Assert $page) => $page
            ->where('analytics.stock_units', 3)
            ->where('analytics.visible_products', 1)
            ->where('analytics.inventory.0.name', 'Electronics')
            ->where('analytics.inventory.0.stock_units', 3)
            ->where('analytics.low_stock_products.0.name', 'Visible low item')
            ->etc());
    }

    public function test_invalid_period_is_rejected_without_database_queries(): void
    {
        $this->actingAs($this->admin())->get('/admin?period=365')->assertSessionHasErrors('period');
        $this->get('/admin?period=abc')->assertSessionHasErrors('period');
    }
}
