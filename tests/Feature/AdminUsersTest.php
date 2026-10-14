<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class AdminUsersTest extends MarketplaceTestCase
{
    public function test_only_admin_can_view_users_and_assign_roles(): void
    {
        $customer = $this->customer();
        $vendor = $this->store()->owner;

        $this->get('/admin/users')->assertRedirect('/login');
        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
        $this->patch("/admin/users/{$vendor->id}/role", [
            'role' => 'admin', 'password' => 'password',
        ])->assertForbidden();
        $this->actingAs($vendor)->get('/admin/users')->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users')
                ->has('users.data', 3)
                ->etc());
    }

    public function test_admin_can_promote_customer_with_own_password(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->patch("/admin/users/{$customer->id}/role", [
            'role' => 'admin', 'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Admin, $customer->fresh()->role);
        $this->actingAs($customer->fresh())->get('/admin/users')->assertOk();
    }

    public function test_wrong_password_cannot_grant_admin_access(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->patch("/admin/users/{$customer->id}/role", [
            'role' => 'admin', 'password' => 'not-the-password',
        ])->assertSessionHasErrors('password');

        $this->assertSame(UserRole::Customer, $customer->fresh()->role);
    }

    public function test_admin_cannot_demote_self_or_only_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/role", [
            'role' => 'customer', 'password' => 'password',
        ])->assertStatus(422);
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_can_demote_another_admin_if_one_remains(): void
    {
        $admin = $this->admin();
        $secondAdmin = $this->admin();

        $this->actingAs($admin)->patch("/admin/users/{$secondAdmin->id}/role", [
            'role' => 'customer', 'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Customer, $secondAdmin->fresh()->role);
        $this->actingAs($secondAdmin->fresh())->get('/admin/users')->assertForbidden();
    }

    public function test_vendor_roles_cannot_be_changed(): void
    {
        $vendor = $this->store()->owner;
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/users/{$vendor->id}/role", [
            'role' => 'admin', 'password' => 'password',
        ])->assertStatus(422);

        $this->assertSame(UserRole::Vendor, $vendor->fresh()->role);
    }

    public function test_role_filter_and_search_return_only_matching_accounts(): void
    {
        $this->customer();
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/users?role=admin&q='.$admin->email)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users')
                ->has('users.data', 1)
                ->where('users.data.0.email', $admin->email)
                ->etc());
    }

    public function test_public_registration_cannot_self_register_admins(): void
    {
        $this->post('/register', [
            'name' => 'Public User', 'email' => 'public@example.test',
            'password' => 'very-secure-pass', 'password_confirmation' => 'very-secure-pass',
            'account_type' => 'admin',
        ])->assertSessionHasErrors('account_type');
        $this->assertDatabaseMissing('users', ['email' => 'public@example.test']);
    }
}
