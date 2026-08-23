<?php

namespace App\Payments;

readonly class PaymentResult
{
    public function __construct(public string $providerReference) {}
}
