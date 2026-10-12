<?php

namespace Tests\Feature;

use App\Enums\StoreStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class AdminAndAuthTest extends MarketplaceTestCase
{
    public function test_admin_can_approve_a_store_which_makes_its_products_visible(): void
    {
        $store = $this->store('pending');
        $this->product($store, ['name' => 'Waiting Item']);

        $this->get('/shop')->assertInertia(fn (Assert $page) => $page->has('products.data', 0));

        $this->actingAs($this->admin())->patch("/admin/stores/{$store->id}", ['status' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->assertSame(StoreStatus::Approved, $store->fresh()->status);
        $this->get('/shop')->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
    }

    public function test_only_admins_can_use_the_admin_area(): void
    {
        $store = $this->store('pending');

        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();
        $this->actingAs($store->owner)->patch("/admin/stores/{$store->id}", ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_customer_registration_logs_in_and_lands_on_the_shop(): void
    {
        $this->post('/register', [
            'name' => 'New Customer', 'email' => 'new@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'account_type' => 'customer',
        ])->assertRedirect('/shop');

        $this->assertAuthenticated();
        $this->assertSame('customer', User::where('email', 'new@example.com')->sole()->role->value);
    }

    public function test_vendor_registration_creates_a_store_pending_approval(): void
    {
        $this->post('/register', [
            'name' => 'New Vendor', 'email' => 'vendor@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'account_type' => 'vendor', 'store_name' => 'My Shiny Store',
        ])->assertRedirect('/vendor');

        $store = User::where('email', 'vendor@example.com')->sole()->store;
        $this->assertSame('My Shiny Store', $store->name);
        $this->assertSame(StoreStatus::Pending, $store->status);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'account_type' => 'admin',
        ])->assertSessionHasErrors('account_type');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertRedirect('/shop');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/shop');
        $this->assertGuest();
    }
}
