<?php

namespace Tests\Feature;

use Tests\MarketplaceTestCase;

class ProductApiTest extends MarketplaceTestCase
{
    public function test_catalogue_is_public_and_only_exposes_listed_products(): void
    {
        $this->product($this->store('approved'), ['name' => 'Public Item', 'price_cents' => 1999]);
        $this->product($this->store('approved'), ['name' => 'Inactive Item', 'is_active' => false]);
        $this->product($this->store('pending'), ['name' => 'Pending Store Item']);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Public Item')
            ->assertJsonPath('data.0.price', 19.99)
            ->assertJsonPath('data.0.in_stock', true);
    }

    public function test_filters_apply_to_the_api(): void
    {
        $store = $this->store();
        $this->product($store, ['name' => 'Cheap Pen', 'price_cents' => 100]);
        $this->product($store, ['name' => 'Fancy Pen', 'price_cents' => 9000]);

        $this->getJson('/api/v1/products?q=Pen&min=10')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fancy Pen');
    }

    public function test_invalid_parameters_are_rejected(): void
    {
        $this->getJson('/api/v1/products?per_page=999')->assertUnprocessable();
    }

    public function test_single_product_endpoint_hides_unlisted_products(): void
    {
        $visible = $this->product($this->store('approved'));
        $hidden = $this->product($this->store('pending'));

        $this->getJson("/api/v1/products/{$visible->slug}")->assertOk()->assertJsonPath('data.slug', $visible->slug);
        $this->getJson("/api/v1/products/{$hidden->slug}")->assertNotFound();
    }
}
