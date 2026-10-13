<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $cart): Response|RedirectResponse
    {
        $items = $cart->items($request->user());

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Checkout/Create', [
            'items' => $items->map(fn (CartItem $i) => [
                'id' => $i->id,
                'name' => $i->product->name,
                'quantity' => $i->quantity,
                'line_cents' => $i->product->price_cents * $i->quantity,
            ])->values(),
            'subtotal_cents' => $cart->subtotalCents($items),
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'shipping_name' => ['required', 'string', 'max:100'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'card_number' => ['required', 'string', 'max:25'],
            'card_expiry' => ['required', 'string', 'max:7'],
            'card_cvc' => ['required', 'string', 'max:4'],
        ]);

        try {
            $order = $checkout->placeOrder(
                $request->user(),
                collect($data)->only(['shipping_name', 'shipping_address', 'shipping_city', 'shipping_phone'])->all(),
                ['number' => $data['card_number'], 'expiry' => $data['card_expiry'], 'cvc' => $data['card_cvc']],
            );
        } catch (CheckoutException $e) {
            // Card details are deliberately NOT flashed back into the form.
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Demo order placed — no money was charged.');
    }
}
