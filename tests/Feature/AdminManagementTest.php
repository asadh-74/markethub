<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class AdminManagementTest extends MarketplaceTestCase
{
    public function test_customers_and_vendors_cannot_read_admin_inventory_or_orders(): void
    {
        $store = $this->store();

        $this->get('/admin/products')->assertRedirect('/login');
        $this->get('/admin/orders')->assertRedirect('/login');

        $this->actingAs($this->customer())->get('/admin/products')->assertForbidden();
        $this->get('/admin/orders')->assertForbidden();

        $this->actingAs($store->owner)->get('/admin/products')->assertForbidden();
        $this->get('/admin/orders')->assertForbidden();
    }

    public function test_admin_can_view_all_products_and_orders(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->orderWith($customer, [[$product, 2]]);

        $this->actingAs($this->admin())
            ->get('/admin/products')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products')
                ->has('products.data', 1)
                ->etc());

        $this->get('/admin/orders')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders')
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $order->number)
                ->etc());

        $this->get('/admin')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.orders', 1)
            ->where('stats.products', 1)
            ->etc());
    }

    public function test_only_admin_can_update_stock_and_listing_visibility(): void
    {
        $product = $this->product(null, ['stock' => 9, 'is_active' => true]);

        $this->actingAs($this->customer())
            ->patch('/admin/products/'.$product->slug, ['stock' => 100])
            ->assertForbidden();

        $this->actingAs($product->store->owner)
            ->patch('/admin/products/'.$product->slug, ['is_active' => false])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->patch('/admin/products/'.$product->slug, ['stock' => 4, 'is_active' => false])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $product->fresh()->stock);
        $this->assertFalse($product->fresh()->is_active);
        $this->get('/shop')->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
    }

    public function test_invalid_admin_inventory_updates_are_rejected(): void
    {
        $product = $this->product(null, ['stock' => 5]);

        $this->actingAs($this->admin())
            ->patch('/admin/products/'.$product->slug, ['stock' => -1])
            ->assertSessionHasErrors('stock');

        $this->patch('/admin/products/'.$product->slug, [])
            ->assertSessionHasErrors('product');

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_admin_can_ship_paid_order_items_and_order_status_is_consistent(): void
    {
        $customer = $this->customer();
        $p1 = $this->product();
        $p2 = $this->product();
        $order = $this->orderWith($customer, [[$p1, 1], [$p2, 1]]);
        [$i1, $i2] = $order->items()->orderBy('id')->get()->all();

        $this->actingAs($this->admin())
            ->patch('/admin/order-items/'.$i1->id.'/ship')
            ->assertSessionHasNoErrors();

        $this->assertSame(FulfillmentStatus::Shipped, $i1->fresh()->fulfillment_status);
        $this->assertSame(FulfillmentStatus::Pending, $i2->fresh()->fulfillment_status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        $this->patch('/admin/order-items/'.$i2->id.'/ship')
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_vendor_cannot_ship_from_admin_and_unpaid_orders_cannot_be_shipped(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->orderWith($customer, [[$product, 1]]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($customer)->patch('/admin/order-items/'.$item->id.'/ship')->assertForbidden();
        $this->actingAs($product->store->owner)->patch('/admin/order-items/'.$item->id.'/ship')->assertForbidden();

        $order->update(['status' => OrderStatus::Pending->value]);
        $this->actingAs($this->admin())
            ->patch('/admin/order-items/'.$item->id.'/ship')
            ->assertStatus(422);

        $this->assertSame(FulfillmentStatus::Pending, $item->fresh()->fulfillment_status);
    }
}
