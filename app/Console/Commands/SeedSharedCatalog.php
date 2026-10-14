<?php

namespace App\Console\Commands;

use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedSharedCatalog extends Command
{
    protected $signature = 'markethub:seed-shared-catalog';

    protected $description = 'Populate an empty shared marketplace once without public sample credentials';

    private const SYSTEM_EMAIL = 'catalog@markethub.invalid';

    public function handle(): int
    {
        // This bootstraps only a completely empty product catalogue.
        // Existing listings, orders and accounts are never modified.
        if (Product::query()->exists()) {
            $this->info('Existing products found; preserving marketplace catalogue.');
            return self::SUCCESS;
        }

        if (User::query()->where('email', self::SYSTEM_EMAIL)->exists()
            || Store::query()->where('slug', 'markethub-demo-store')->exists()) {
            $this->info('Shared sample catalogue was already initialized; not recreating deleted products.');
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            // Not a usable public login: the random secret is never printed,
            // placed in GitHub or disclosed through a demo readme.
            $owner = User::create([
                'name' => 'MarketHub Demo Catalog (System)',
                'email' => self::SYSTEM_EMAIL,
                'password' => Str::random(80),
                'role' => UserRole::Vendor->value,
            ]);

            $store = Store::forceCreate([
                'user_id' => $owner->id,
                'name' => 'MarketHub Demo Store',
                'slug' => 'markethub-demo-store',
                'description' => 'Portfolio demonstration catalogue with sample products and simulated checkout.',
                'status' => StoreStatus::Approved->value,
            ]);

            // Prices are stored in paise, displayed in PKR by the frontend.
            $products = [
                ['Wireless Earbuds', 'Electronics', 799900, 25, 'Compact Bluetooth earbuds with charging case, a clear microphone and all-day listening.', 'photo-1572569511254-d8f925fe2cbb'],
                ['Mechanical RGB Keyboard', 'Electronics', 1299900, 18, 'Tactile mechanical switches, full-size layout and customizable RGB backlighting.', 'photo-1587829741301-dc798b83add3'],
                ['Studio Headphones', 'Electronics', 1099900, 14, 'Comfortable over-ear headphones with balanced sound and soft ear cushions.', 'photo-1505740420928-5e560c06d30e'],
                ['Minimalist Wristwatch', 'Fashion', 649900, 19, 'Everyday analogue watch with a clean dial and adjustable strap.', 'photo-1523275335684-37898b6baf30'],
                ['Ceramic Coffee Mug', 'Home & Kitchen', 149900, 35, 'Everyday stoneware mug with an easy-grip handle and minimalist finish.', 'photo-1514228742587-6b1558fcca3d'],
                ['Coffee Maker', 'Home & Kitchen', 1199900, 12, 'Compact home coffee maker designed for simple everyday brewing.', 'photo-1517668808822-9ebb02f2a0e6'],
                ['Running Shoes', 'Sports', 999900, 17, 'Lightweight trainers with breathable uppers and cushioned soles.', 'photo-1542291026-7eec264c27ff'],
                ['Yoga Mat', 'Sports', 299900, 28, 'Comfortable non-slip exercise mat for yoga, stretching and home workouts.', 'photo-1599447421416-3414500d18a5'],
                ['Denim Jacket', 'Fashion', 749900, 16, 'Classic blue denim jacket with a relaxed fit and practical pockets.', 'photo-1544022613-e87ca75a784a'],
                ['Creative Reading Set', 'Books', 349900, 22, 'A curated starter set for readers interested in creativity and personal growth.', 'photo-1481627834876-b7833e8f5570'],
            ];

            foreach ($products as [$name, $categoryName, $price, $stock, $description, $photo]) {
                $category = Category::firstOrCreate([
                    'slug' => Str::slug($categoryName),
                ], ['name' => $categoryName]);

                Product::forceCreate([
                    'store_id' => $store->id,
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => 'demo-'.Str::slug($name),
                    'description' => $description,
                    'price_cents' => $price,
                    'stock' => $stock,
                    'is_active' => true,
                    'image_url' => 'https://images.unsplash.com/'.$photo.'?auto=format&fit=crop&w=960&q=80',
                ]);
            }
        });

        $this->info('Shared catalogue created: 10 products in one approved demo store. No public demo passwords.');
        return self::SUCCESS;
    }
}
