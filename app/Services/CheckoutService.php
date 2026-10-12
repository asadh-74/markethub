<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\CheckoutException;
use App\Exceptions\PaymentFailedException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
    }

    /**
     * Turns the user's cart into a paid order, atomically:
     * stock is locked, validated and decremented, the card is charged, and
     * ANY failure rolls everything back (no half-created orders, no lost stock).
     *
     * @param  array{shipping_name: string, shipping_address: string, shipping_city: string, shipping_phone: string}  $shipping
     * @param  array{number: string, expiry: string, cvc: string}  $card
     *
     * @throws CheckoutException
     */
    public function placeOrder(User $user, array $shipping, array $card): Order
    {
        $order = DB::transaction(function () use ($user, $shipping, $card) {
            $cart = CartItem::where('user_id', $user->id)->get();

            if ($cart->isEmpty()) {
                throw new CheckoutException('Your cart is empty.');
            }

            // Lock the product rows so two shoppers cannot buy the last unit at once.
            $products = Product::with('store')
                ->whereIn('id', $cart->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = Order::create($shipping + [
                'user_id' => $user->id,
                'number' => 'MH-'.Str::upper(Str::random(8)),
                'status' => OrderStatus::Pending->value,
                'subtotal_cents' => 0,
                'total_cents' => 0,
            ]);

            $total = 0;

            foreach ($cart as $line) {
                $product = $products->get($line->product_id);

                if (! $product || ! $product->is_active || ! $product->store->isApproved()) {
                    throw new CheckoutException('A product in your cart is no longer available.');
                }

                if ($line->quantity > $product->stock) {
                    throw new CheckoutException("Only {$product->stock} left of {$product->name}.");
                }

                $product->decrement('stock', $line->quantity);

                $order->items()->create([
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'name' => $product->name,
                    'unit_price_cents' => $product->price_cents,
                    'quantity' => $line->quantity,
                ]);

                $total += $product->price_cents * $line->quantity;
            }

            $result = $this->gateway->charge($total, $card, $order->number);

            if (! $result->success) {
                throw new PaymentFailedException($result->message ?? 'Payment failed.');
            }

            $order->update([
                'subtotal_cents' => $total,
                'total_cents' => $total,
                'status' => OrderStatus::Paid->value,
                'payment_reference' => $result->reference,
            ]);

            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });

        // Only after the transaction has committed: queue emails / notifications.
        OrderPlaced::dispatch($order);

        return $order;
    }
}
