<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Product;
use Tests\MarketplaceTestCase;

class VendorTest extends MarketplaceTestCase
{
    public function test_vendor_with_an_approved_store_can_create_a_product(): void
    {
        $vendor = $this->store('approved')->owner;

        $this->actingAs($vendor)->post('/vendor/products', [
            'name' => 'Handmade Mug',
            'description' => 'Ceramic',
            'price' => 19.99,
            'stock' => 7,
            'is_active' => true,
        ])->assertRedirect('/vendor/products');

        $product = Product::sole();
        $this->assertSame(1999, $product->price_cents);   // stored as integer cents
        $this->assertSame($vendor->store->id, $product->store_id);
        $this->assertNotEmpty($product->slug);
    }

    public function test_product_validation_errors(): void
    {
        $vendor = $this->store('approved')->owner;

        $this->actingAs($vendor)->post('/vendor/products', ['name' => '', 'price' => -5, 'stock' => 'abc'])
            ->assertSessionHasErrors(['name', 'price', 'stock']);
    }

    public function test_vendor_with_a_pending_store_cannot_create_products(): void
    {
        $vendor = $this->store('pending')->owner;

        $this->actingAs($vendor)->post('/vendor/products', ['name' => 'X', 'price' => 5, 'stock' => 1])->assertForbidden();
        $this->actingAs($vendor)->get('/vendor/products/create')->assertForbidden();
    }

    public function test_vendor_can_edit_own_products_but_not_another_vendors(): void
    {
        $mine = $this->product($this->store(), ['name' => 'Mine']);
        $theirs = $this->product($this->store(), ['name' => 'Theirs']);
        $vendor = $mine->store->owner;

        $this->actingAs($vendor)->put("/vendor/products/{$mine->slug}", [
            'name' => 'Mine v2', 'price' => 10, 'stock' => 3, 'is_active' => true,
        ])->assertRedirect('/vendor/products');
        $this->assertSame('Mine v2', $mine->fresh()->name);

        $this->actingAs($vendor)->get("/vendor/products/{$theirs->slug}/edit")->assertForbidden();
        $this->actingAs($vendor)->put("/vendor/products/{$theirs->slug}", ['name' => 'Hacked', 'price' => 1, 'stock' => 1])->assertForbidden();
        $this->actingAs($vendor)->delete("/vendor/products/{$theirs->slug}")->assertForbidden();
        $this->assertSame('Theirs', $theirs->fresh()->name);
    }

    public function test_customers_and_guests_cannot_open_the_vendor_area(): void
    {
        $this->get('/vendor')->assertRedirect('/login');
        $this->actingAs($this->customer())->get('/vendor')->assertForbidden();
    }

    public function test_vendor_can_ship_only_their_own_items_and_order_becomes_shipped_when_all_are_shipped(): void
    {
        $storeA = $this->store();
        $storeB = $this->store();
        $order = $this->orderWith($this->customer(), [
            [$this->product($storeA), 1],
            [$this->product($storeB), 1],
        ]);
        [$itemA, $itemB] = [$order->items[0], $order->items[1]];

        $this->actingAs($storeA->owner)->patch("/vendor/order-items/{$itemB->id}/ship")->assertForbidden();

        $this->actingAs($storeA->owner)->patch("/vendor/order-items/{$itemA->id}/ship");
        $this->assertSame(FulfillmentStatus::Shipped, $itemA->fresh()->fulfillment_status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);   // seller B has not shipped yet

        $this->actingAs($storeB->owner)->patch("/vendor/order-items/{$itemB->id}/ship");
        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_vendor_dashboard_loads(): void
    {
        $store = $this->store();
        $this->orderWith($this->customer(), [[$this->product($store, ['price_cents' => 1000]), 2]]);

        $this->actingAs($store->owner)->get('/vendor')->assertOk();
    }
}
