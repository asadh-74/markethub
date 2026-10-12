<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function index(Request $request): Response
    {
        $items = $this->cart->items($request->user());

        return Inertia::render('Cart/Index', [
            'items' => $items->map(fn (CartItem $i) => [
                'id' => $i->id,
                'quantity' => $i->quantity,
                'line_cents' => $i->product->price_cents * $i->quantity,
                'product' => $i->product->toCard(),
            ])->values(),
            'subtotal_cents' => $this->cart->subtotalCents($items),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Product::listed()->whereKey($product->id)->exists(), 404);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);

        $this->cart->add($request->user(), $product, $data['quantity']);

        return back()->with('success', "{$product->name} added to your cart.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);

        $this->cart->setQuantity($item, $data['quantity']);

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return back()->with('success', 'Item removed.');
    }
}
