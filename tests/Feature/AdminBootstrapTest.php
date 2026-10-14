<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\MarketplaceTestCase;

class AdminBootstrapTest extends MarketplaceTestCase
{
    protected function tearDown(): void
    {
        putenv('MARKETHUB_ADMIN_EMAIL');
        putenv('MARKETHUB_ADMIN_PASSWORD');
        parent::tearDown();
    }

    public function test_command_creates_first_admin_once_without_resetting_password(): void
    {
        putenv('MARKETHUB_ADMIN_EMAIL=owner-admin@example.test');
        putenv('MARKETHUB_ADMIN_PASSWORD=temporary-secure-passphrase');

        $this->artisan('markethub:bootstrap-admin')->assertSuccessful();

        $admin = User::where('email', 'owner-admin@example.test')->sole();
        $this->assertSame(UserRole::Admin, $admin->role);
        $passwordHash = $admin->password;

        putenv('MARKETHUB_ADMIN_PASSWORD=another-secure-passphrase');
        $this->artisan('markethub:bootstrap-admin')->assertSuccessful();

        $this->assertSame(1, User::where('email', 'owner-admin@example.test')->count());
        $this->assertSame($passwordHash, $admin->fresh()->password);
    }

    public function test_command_refuses_to_promote_an_existing_customer(): void
    {
        $customer = $this->customer();
        putenv('MARKETHUB_ADMIN_EMAIL='.$customer->email);
        putenv('MARKETHUB_ADMIN_PASSWORD=temporary-secure-passphrase');

        $this->artisan('markethub:bootstrap-admin')->assertFailed();

        $this->assertSame(UserRole::Customer, $customer->fresh()->role);
    }

    public function test_command_requires_secure_parameters(): void
    {
        putenv('MARKETHUB_ADMIN_EMAIL=owner-admin@example.test');
        putenv('MARKETHUB_ADMIN_PASSWORD=short');

        $this->artisan('markethub:bootstrap-admin')->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'owner-admin@example.test']);
    }
}
