<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\MarketplaceTestCase;

class ShopTest extends MarketplaceTestCase
{
    public function test_shop_only_lists_active_products_from_approved_stores(): void
    {
        $approved = $this->store('approved');
        $this->product($approved, ['name' => 'Visible Item', 'is_active' => true]);
        $this->product($approved, ['name' => 'Hidden Item', 'is_active' => false]);
        $this->product($this->store('pending'), ['name' => 'Unapproved Store Item']);

        $this->get('/shop')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Visible Item'));
    }

    public function test_search_and_price_filters_narrow_the_results(): void
    {
        $store = $this->store();
        $this->product($store, ['name' => 'Red Lamp', 'price_cents' => 1000]);
        $this->product($store, ['name' => 'Blue Lamp', 'price_cents' => 9000]);
        $this->product($store, ['name' => 'Garden Chair', 'price_cents' => 2000]);

        $this->get('/shop?q=Lamp&max=50')
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Red Lamp'));
    }

    public function test_sorting_by_price(): void
    {
        $store = $this->store();
        $this->product($store, ['name' => 'Cheap', 'price_cents' => 100]);
        $this->product($store, ['name' => 'Pricey', 'price_cents' => 9900]);

        $this->get('/shop?sort=price_desc')
            ->assertInertia(fn (Assert $page) => $page->where('products.data.0.name', 'Pricey'));
    }

    public function test_product_page_is_hidden_when_the_store_is_not_approved(): void
    {
        $visible = $this->product($this->store('approved'));
        $hidden = $this->product($this->store('pending'));

        $this->get("/products/{$visible->slug}")->assertOk();
        $this->get("/products/{$hidden->slug}")->assertNotFound();
    }
}
