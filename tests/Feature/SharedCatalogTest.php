<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class SharedCatalogTest extends MarketplaceTestCase
{
    public function test_seed_adds_listings_visible_to_guests_and_all_customers(): void
    {
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();

        $this->assertDatabaseCount('products', 10);
        $store = Store::where('slug', 'markethub-demo-store')->sole();
        $this->assertTrue($store->isApproved());
        $this->assertSame(UserRole::Vendor, $store->owner->role);

        $this->get('/shop')->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Index')->has('products.data', 10));

        $this->actingAs($this->customer())->get('/shop')
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 10));

        $this->actingAs($this->customer())->get('/shop')
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 10));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_seed_is_idempotent_and_preserves_real_users_and_order_records(): void
    {
        $existingCustomer = $this->customer();
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();
        $firstPrice = Product::where('slug', 'demo-wireless-earbuds')->sole()->price_cents;

        Product::where('slug', 'demo-wireless-earbuds')->update(['price_cents' => $firstPrice + 1000]);
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();

        $this->assertDatabaseCount('products', 10);
        $this->assertTrue($existingCustomer->fresh()->exists);
        $this->assertSame($firstPrice + 1000, Product::where('slug', 'demo-wireless-earbuds')->sole()->price_cents);
    }

    public function test_seed_skips_existing_seller_catalogue(): void
    {
        $product = $this->product();
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseMissing('users', ['email' => 'catalog@markethub.invalid']);
        $this->assertTrue($product->fresh()->exists);
    }

    public function test_seeder_will_not_recreate_deleted_catalog_items_or_public_passwords(): void
    {
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();

        $systemOwner = User::where('email', 'catalog@markethub.invalid')->sole();
        $this->assertTrue(Hash::info($systemOwner->password)['algoName'] !== 'unknown');

        Product::query()->delete();
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(1, Store::where('slug', 'markethub-demo-store')->count());
    }

    public function test_seed_adds_no_public_admin_account(): void
    {
        $this->artisan('markethub:seed-shared-catalog')->assertSuccessful();
        $this->assertDatabaseMissing('users', ['role' => 'admin']);
        $this->assertDatabaseMissing('users', ['email' => 'admin@markethub.test']);
    }
}
