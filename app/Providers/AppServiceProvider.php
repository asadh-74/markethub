<?php

namespace App\Providers;

use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap FakeGateway for a Stripe/PayPal implementation without touching the checkout code.
        $this->app->bind(PaymentGateway::class, FakeGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
