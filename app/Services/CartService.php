<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /** @return Collection<int, CartItem> */
    public function items(User $user): Collection
    {
        return CartItem::with('product.store')->where('user_id', $user->id)->orderBy('id')->get();
    }

    public function add(User $user, Product $product, int $quantity): void
    {
        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);
        $newQuantity = ($item->exists ? $item->quantity : 0) + $quantity;

        $this->assertInStock($product, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();
    }

    public function setQuantity(CartItem $item, int $quantity): void
    {
        $this->assertInStock($item->product, $quantity);

        $item->update(['quantity' => $quantity]);
    }

    /** @param Collection<int, CartItem> $items */
    public function subtotalCents(Collection $items): int
    {
        return (int) $items->sum(fn (CartItem $i) => $i->product->price_cents * $i->quantity);
    }

    private function assertInStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock} of {$product->name} in stock.",
            ]);
        }
    }
}
