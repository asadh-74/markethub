<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class LoginPageTest extends MarketplaceTestCase
{
    public function test_production_style_login_does_not_offer_obsolete_public_demo_passwords(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('showDemoAccounts', false)
            ->etc());
    }

    public function test_local_sqlite_demo_can_show_shortcuts(): void
    {
        $original = config('database.default');
        config()->set('database.default', 'sqlite');
        app()->detectEnvironment(fn () => 'local');

        try {
            $this->get('/login')->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('showDemoAccounts', true)
                ->etc());
        } finally {
            config()->set('database.default', $original);
            app()->detectEnvironment(fn () => 'testing');
        }
    }
}
