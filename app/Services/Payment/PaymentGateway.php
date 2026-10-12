<?php

namespace App\Services\Payment;

interface PaymentGateway
{
    /**
     * @param  array{number?: string, expiry?: string, cvc?: string}  $card
     */
    public function charge(int $amountCents, array $card, string $reference): PaymentResult;
}
