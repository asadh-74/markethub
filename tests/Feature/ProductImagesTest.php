<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\MarketplaceTestCase;

class ProductImagesTest extends MarketplaceTestCase
{
    public function test_vendor_can_publish_an_https_product_photo(): void
    {
        $vendor = $this->store('approved')->owner;

        $this->actingAs($vendor)->post('/vendor/products', [
            'name' => 'Studio headphones',
            'price' => 99.50,
            'stock' => 10,
            'image_url' => 'https://cdn.example.com/headphones.jpg',
        ])->assertRedirect('/vendor/products');

        $product = Product::sole();
        $this->assertSame('https://cdn.example.com/headphones.jpg', $product->image_url);
        $this->assertSame($product->image_url, $product->toCard()['image_url']);

        $this->get('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.image_url', 'https://cdn.example.com/headphones.jpg');
    }

    public function test_vendor_cannot_publish_a_javascript_url(): void
    {
        $vendor = $this->store('approved')->owner;

        $this->actingAs($vendor)->post('/vendor/products', [
            'name' => 'Bad image',
            'price' => 15,
            'stock' => 5,
            'image_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('image_url');

        $this->assertDatabaseCount('products', 0);
    }
}
