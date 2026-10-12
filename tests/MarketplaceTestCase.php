<?php

namespace Tests;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class MarketplaceTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The Vue assets are not built during tests.
        $this->withoutVite();
    }

    protected function customer(): User
    {
        return User::factory()->create(['role' => UserRole::Customer->value]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin->value]);
    }

    protected function store(string $status = 'approved'): Store
    {
        return Store::factory()->create(['status' => $status]);
    }

    protected function product(?Store $store = null, array $attributes = []): Product
    {
        return Product::factory()->for($store ?? $this->store())->create($attributes);
    }

    /** A paid order for $customer containing one line per given [product, quantity] pair. */
    protected function orderWith(User $customer, array $lines): Order
    {
        $order = Order::create([
            'user_id' => $customer->id,
            'number' => 'MH-'.strtoupper(substr(md5((string) microtime(true).random_int(1, 99999)), 0, 8)),
            'status' => OrderStatus::Paid->value,
            'subtotal_cents' => 0,
            'total_cents' => 0,
            'shipping_name' => 'Test Buyer',
            'shipping_address' => '1 Test Street',
            'shipping_city' => 'Islamabad',
            'shipping_phone' => '0300',
        ]);

        foreach ($lines as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'name' => $product->name,
                'unit_price_cents' => $product->price_cents,
                'quantity' => $quantity,
                'fulfillment_status' => FulfillmentStatus::Pending->value,
            ]);
        }

        return $order;
    }
}
