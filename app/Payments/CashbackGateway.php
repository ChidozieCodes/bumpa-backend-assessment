<?php

namespace App\Payments;

use App\Models\User;

interface CashbackGateway
{
    public function send(User $user, int $amountKobo, string $reference): PaymentResult;

    public function provider(): string;
}
