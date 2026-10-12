<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Models\CartItem;
use App\Models\Order;
use App\Notifications\LowStockNotification;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\MarketplaceTestCase;

class CheckoutTest extends MarketplaceTestCase
{
    private function payload(string $card = '4242 4242 4242 4242'): array
    {
        return [
            'shipping_name' => 'Ayesha Khan',
            'shipping_address' => 'House 1, Street 2',
            'shipping_city' => 'Islamabad',
            'shipping_phone' => '+92 300 1234567',
            'card_number' => $card,
            'card_expiry' => '12/30',
            'card_cvc' => '123',
        ];
    }

    public function test_successful_checkout_creates_a_paid_order_and_updates_stock(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $storeA = $this->store();
        $storeB = $this->store();
        $laptop = $this->product($storeA, ['price_cents' => 10000, 'stock' => 10]);
        $mouse = $this->product($storeB, ['price_cents' => 2500, 'stock' => 20]);

        CartItem::create(['user_id' => $customer->id, 'product_id' => $laptop->id, 'quantity' => 1]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $mouse->id, 'quantity' => 2]);

        $response = $this->actingAs($customer)->post('/checkout', $this->payload());

        $order = Order::with('items')->sole();
        $response->assertRedirect("/orders/{$order->number}");

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(15000, $order->total_cents);
        $this->assertCount(2, $order->items);
        $this->assertSame(9, $laptop->fresh()->stock);
        $this->assertSame(18, $mouse->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);

        // Customer gets one confirmation, each seller gets their own notification.
        Notification::assertSentTo($customer, OrderConfirmationNotification::class);
        Notification::assertSentTo($storeA->owner, NewOrderNotification::class);
        Notification::assertSentTo($storeB->owner, NewOrderNotification::class);
    }

    public function test_order_items_keep_the_price_paid_even_if_the_product_price_changes_later(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $product = $this->product(null, ['price_cents' => 5000, 'stock' => 5]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', $this->payload());
        $product->update(['price_cents' => 9999]);

        $this->assertSame(5000, Order::sole()->items->sole()->unit_price_cents);
    }

    public function test_declined_card_rolls_everything_back(): void
    {
        Event::fake([OrderPlaced::class]);

        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 10]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($customer)->post('/checkout', $this->payload('4000 0000 0000 0002'))
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(10, $product->fresh()->stock);   // stock restored
        $this->assertDatabaseCount('cart_items', 1);        // cart kept so they can retry
        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_checkout_is_refused_when_stock_ran_out(): void
    {
        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 1]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($customer)->post('/checkout', $this->payload())
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_is_refused_when_the_store_was_suspended(): void
    {
        $customer = $this->customer();
        $store = $this->store();
        $product = $this->product($store, ['stock' => 5]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

        $store->update(['status' => 'suspended']);

        $this->actingAs($customer)->post('/checkout', $this->payload())->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_placed_event_is_dispatched_after_a_successful_checkout(): void
    {
        Event::fake([OrderPlaced::class]);

        $customer = $this->customer();
        $product = $this->product(null, ['stock' => 5]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', $this->payload());

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_vendor_is_warned_when_stock_runs_low(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $store = $this->store();
        $product = $this->product($store, ['stock' => 6]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($customer)->post('/checkout', $this->payload());

        Notification::assertSentTo($store->owner, LowStockNotification::class);
    }

    public function test_empty_cart_cannot_reach_checkout(): void
    {
        $this->actingAs($this->customer())->get('/checkout')->assertRedirect('/cart');
    }

    public function test_customers_can_only_view_their_own_orders(): void
    {
        $buyer = $this->customer();
        $order = $this->orderWith($buyer, [[$this->product(), 1]]);

        $this->actingAs($buyer)->get("/orders/{$order->number}")->assertOk();
        $this->actingAs($this->customer())->get("/orders/{$order->number}")->assertForbidden();
        $this->actingAs($this->admin())->get("/orders/{$order->number}")->assertOk();
    }
}
