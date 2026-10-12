<?php

namespace Database\Seeders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin', 'email' => 'admin@markethub.test',
            'password' => 'password', 'role' => UserRole::Admin->value,
        ]);
        $customer = User::factory()->create([
            'name' => 'Ayesha Customer', 'email' => 'customer@markethub.test',
            'password' => 'password', 'role' => UserRole::Customer->value,
        ]);

        $categories = collect(['Electronics', 'Home & Kitchen', 'Fashion', 'Books', 'Sports'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name, 'slug' => Str::slug($name)])]);

        $stores = [
            ['vendor1@markethub.test', 'Bilal Vendor', 'TechNest', StoreStatus::Approved, [
                ['Wireless Earbuds', 'Electronics', 7999, 25, 'Bluetooth 5.3, 24h battery with case.'],
                ['Mechanical Keyboard', 'Electronics', 12999, 12, 'Hot-swappable, tactile switches, RGB.'],
                ['USB-C Fast Charger 65W', 'Electronics', 3499, 40, 'GaN charger for laptop, phone and tablet.'],
                ['Portable Power Bank 20000mAh', 'Electronics', 5499, 3, 'Dual output, LED battery indicator.'],
                ['27" 4K Monitor', 'Electronics', 54999, 6, 'IPS panel, 60Hz, USB-C input.'],
            ]],
            ['vendor2@markethub.test', 'Sana Vendor', 'Urban Home', StoreStatus::Approved, [
                ['Ceramic Dinner Set (16 pcs)', 'Home & Kitchen', 8999, 18, 'Microwave and dishwasher safe.'],
                ['Cast Iron Skillet', 'Home & Kitchen', 4599, 22, 'Pre-seasoned, 26 cm.'],
                ['Cotton Bedsheet Set', 'Home & Kitchen', 6499, 30, 'King size, 300 thread count.'],
                ['Running Shoes', 'Sports', 9999, 15, 'Lightweight mesh upper, cushioned sole.'],
                ['Yoga Mat 6mm', 'Sports', 2499, 4, 'Non-slip, carrying strap included.'],
                ['Denim Jacket', 'Fashion', 7499, 14, 'Classic fit, washed blue.'],
            ]],
            ['vendor3@markethub.test', 'Hamza Vendor', 'Page Turner Books', StoreStatus::Pending, [
                ['Clean Code (Paperback)', 'Books', 3999, 20, 'A handbook of agile software craftsmanship.'],
            ]],
        ];

        $createdProducts = [];

        foreach ($stores as [$email, $ownerName, $storeName, $status, $products]) {
            $owner = User::factory()->create([
                'name' => $ownerName, 'email' => $email,
                'password' => 'password', 'role' => UserRole::Vendor->value,
            ]);

            $store = Store::forceCreate([
                'user_id' => $owner->id,
                'name' => $storeName,
                'slug' => Str::slug($storeName),
                'description' => "Welcome to {$storeName}.",
                'status' => $status->value,
            ]);

            foreach ($products as [$name, $category, $price, $stock, $description]) {
                $createdProducts[$name] = Product::forceCreate([
                    'store_id' => $store->id,
                    'category_id' => $categories[$category]->id,
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
                    'description' => $description,
                    'price_cents' => $price,
                    'stock' => $stock,
                ]);
            }
        }

        // One historical order so the vendor dashboards have data on first login.
        $order = Order::create([
            'user_id' => $customer->id,
            'number' => 'MH-DEMO0001',
            'status' => OrderStatus::Paid->value,
            'subtotal_cents' => 0,
            'total_cents' => 0,
            'payment_reference' => 'fake_seeded',
            'shipping_name' => 'Ayesha Customer',
            'shipping_address' => 'House 12, Street 4, F-7',
            'shipping_city' => 'Islamabad',
            'shipping_phone' => '+92 300 0000000',
        ]);

        $total = 0;
        foreach ([['Wireless Earbuds', 1], ['Yoga Mat 6mm', 2]] as [$name, $qty]) {
            $product = $createdProducts[$name];
            $order->items()->create([
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'name' => $product->name,
                'unit_price_cents' => $product->price_cents,
                'quantity' => $qty,
                'fulfillment_status' => FulfillmentStatus::Pending->value,
            ]);
            $total += $product->price_cents * $qty;
        }
        $order->update(['subtotal_cents' => $total, 'total_cents' => $total]);
    }
}
