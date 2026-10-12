<?php

namespace Tests\Feature;

use App\Models\CartItem;
use Tests\MarketplaceTestCase;

class CartTest extends MarketplaceTestCase
{
    public function test_guests_are_sent_to_login_when_adding_to_the_cart(): void
    {
        $product = $this->product();

        $this->post("/cart/{$product->slug}", ['quantity' => 1])->assertRedirect('/login');
    }

    public function test_customer_can_add_the_same_product_twice_and_quantities_merge(): void
    {
        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 10]);

        $this->actingAs($customer)->post("/cart/{$product->slug}", ['quantity' => 2]);
        $this->actingAs($customer)->post("/cart/{$product->slug}", ['quantity' => 3]);

        $this->assertSame(5, CartItem::where('user_id', $customer->id)->sole()->quantity);
    }

    public function test_cannot_add_more_than_the_available_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 2]);

        $this->actingAs($customer)->post("/cart/{$product->slug}", ['quantity' => 3])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_can_update_and_remove_items(): void
    {
        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 10]);
        $item = CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)->patch("/cart/items/{$item->id}", ['quantity' => 4]);
        $this->assertSame(4, $item->fresh()->quantity);

        $this->actingAs($customer)->delete("/cart/items/{$item->id}");
        $this->assertModelMissing($item);
    }

    public function test_a_customer_cannot_touch_someone_elses_cart(): void
    {
        $owner = $this->customer();
        $product = $this->product();
        $item = CartItem::create(['user_id' => $owner->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($this->customer())->patch("/cart/items/{$item->id}", ['quantity' => 2])->assertForbidden();
        $this->actingAs($this->customer())->delete("/cart/items/{$item->id}")->assertForbidden();
    }
}
