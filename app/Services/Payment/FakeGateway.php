<?php

namespace App\Services\Payment;

use Illuminate\Support\Str;

/**
 * Demo gateway. Card data is never stored - it is only inspected here.
 *   any 12+ digit number  -> approved
 *   number ending 0002    -> declined
 *   shorter numbers       -> invalid
 */
class FakeGateway implements PaymentGateway
{
    public function charge(int $amountCents, array $card, string $reference): PaymentResult
    {
        $number = preg_replace('/\D+/', '', $card['number'] ?? '');

        if (strlen($number) < 12) {
            return new PaymentResult(false, null, 'Invalid card number.');
        }

        if (str_ends_with($number, '0002')) {
            return new PaymentResult(false, null, 'Your card was declined.');
        }

        return new PaymentResult(true, 'fake_'.Str::lower(Str::random(14)));
    }
}
