<?php

namespace App\Payments;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class LogCashbackGateway implements CashbackGateway
{
    public function send(User $user, int $amountKobo, string $reference): PaymentResult
    {
        Log::info('Simulated cashback transfer', [
            'amount_kobo' => $amountKobo,
            'reference' => $reference,
            'user_id' => $user->id,
        ]);

        return new PaymentResult('simulated-'.$reference);
    }

    public function provider(): string
    {
        return 'log';
    }
}
